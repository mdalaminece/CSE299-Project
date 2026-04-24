from pathlib import Path
from fastapi import FastAPI, Request, Query
from fastapi.responses import PlainTextResponse
import requests
import os
from dotenv import load_dotenv
import tempfile
from gtts import gTTS

# Import our helper modules
from db import (
    get_db,
    get_chat_history,
    save_chat_message,
)
from groq_ai import detect_intent, generate_natural_response, transcribe_audio

load_dotenv(dotenv_path=Path(__file__).resolve().parent / ".env")

app = FastAPI()

PAGE_ACCESS_TOKEN = os.getenv("PAGE_ACCESS_TOKEN")
PAGE_ID = os.getenv("PAGE_ID")
VERIFY_TOKEN = os.getenv("VERIFY_TOKEN")
TELEGRAM_BOT_TOKEN = os.getenv("TELEGRAM_BOT")
GRAPH_API_URL = "https://graph.facebook.com/v18.0/me/messages"
TELEGRAM_API_URL = f"https://api.telegram.org/bot{TELEGRAM_BOT_TOKEN}/sendMessage" if TELEGRAM_BOT_TOKEN else None
BUY_FORM_URL = "https://docs.google.com/forms/d/e/1FAIpQLSeE6fQ1M8MWhteeSvob0Sx-V94ZycZpUuk8sjWypPNr-4--BA/viewform?usp=dialog"



# =================================================
# 1️⃣ WEBHOOK VERIFICATION (GET)
# =================================================
@app.get("/webhook")
async def verify_webhook(
    hub_mode: str = Query(None, alias="hub.mode"),
    hub_verify_token: str = Query(None, alias="hub.verify_token"),
    hub_challenge: str = Query(None, alias="hub.challenge")
):
    if hub_mode == "subscribe" and hub_verify_token == VERIFY_TOKEN and hub_challenge:
        return PlainTextResponse(content=hub_challenge)

    return PlainTextResponse(content="Verification failed", status_code=403)


# =================================================
# 2️⃣ RECEIVE MESSAGE FROM FACEBOOK (POST)
# =================================================
@app.post("/webhook")
async def receive_message(request: Request):
    data = await request.json()
    print("===== INCOMING WEBHOOK DATA =====")
    print(data)
    print("=================================")

    if data.get("object") == "page":
        for entry in data.get("entry", []):
            for messaging_event in entry.get("messaging", []):
                
                if messaging_event.get("message", {}).get("is_echo"):
                    continue

                if "message" in messaging_event:
                    sender_id = messaging_event["sender"]["id"]
                    message_obj = messaging_event["message"]
                    text = message_obj.get("text")
                    
                    attachments = message_obj.get("attachments", [])
                    audio_url = None
                    for attachment in attachments:
                        if attachment.get("type") in ["audio", "voice"]:
                            audio_url = attachment.get("payload", {}).get("url")
                            break
                    
                    if audio_url:
                        # Download audio from URL
                        temp_audio = tempfile.NamedTemporaryFile(delete=False, suffix=".mp4")
                        audio_res = requests.get(audio_url)
                        temp_audio.write(audio_res.content)
                        temp_audio.close()
                        
                        transcribed_text = transcribe_audio(temp_audio.name)
                        os.unlink(temp_audio.name)
                        
                        if transcribed_text:
                            process_message(sender_id, transcribed_text, platform="facebook", respond_voice=True)
                    elif text:
                        # Process logic synchronously
                        process_message(sender_id, text, platform="facebook")

    return {"status": "ok"}


# =================================================
# 3️⃣ RECEIVE MESSAGE FROM TELEGRAM (POST)
# =================================================
@app.post("/telegram-webhook")
async def telegram_webhook(request: Request):
    data = await request.json()
    print("===== INCOMING TELEGRAM WEBHOOK DATA =====")
    print(data)
    print("==========================================")

    if "message" in data:
        chat_id = data["message"]["chat"]["id"]
        text = data["message"].get("text")
        voice = data["message"].get("voice") or data["message"].get("audio")

        if voice:
            file_id = voice["file_id"]
            file_info_url = f"https://api.telegram.org/bot{TELEGRAM_BOT_TOKEN}/getFile?file_id={file_id}"
            file_info_res = requests.get(file_info_url).json()
            if file_info_res.get("ok"):
                file_path = file_info_res["result"]["file_path"]
                download_url = f"https://api.telegram.org/file/bot{TELEGRAM_BOT_TOKEN}/{file_path}"
                
                temp_audio = tempfile.NamedTemporaryFile(delete=False, suffix=".ogg")
                dl_res = requests.get(download_url)
                temp_audio.write(dl_res.content)
                temp_audio.close()
                
                transcribed_text = transcribe_audio(temp_audio.name)
                os.unlink(temp_audio.name)
                
                if transcribed_text:
                    process_message(str(chat_id), transcribed_text, platform="telegram", respond_voice=True)
        elif text:
            process_message(str(chat_id), text, platform="telegram")

    return {"status": "ok"}


def send_reply(user_id, reply_text, platform, respond_voice):
    if respond_voice:
        audio_file = tempfile.NamedTemporaryFile(delete=False, suffix=".mp3")
        audio_file.close() # Close it so gTTS can write to it
        
        try:
            gTTS(text=reply_text, lang='en').save(audio_file.name)
            if platform == "telegram":
                send_telegram_audio(user_id, audio_file.name)
            else:
                send_facebook_audio(user_id, audio_file.name)
        except Exception as e:
            print(f"Error generating or sending audio: {e}")
            # Fallback to text
            if platform == "telegram":
                send_telegram_message(user_id, reply_text)
            else:
                send_facebook_message(user_id, reply_text)
        finally:
            if os.path.exists(audio_file.name):
                os.unlink(audio_file.name)
    else:
        if platform == "telegram":
            send_telegram_message(user_id, reply_text)
        else:
            send_facebook_message(user_id, reply_text)


def process_message(user_id, message, platform="facebook", respond_voice=False):
    if is_buy_request(message):
        reply = f"Please fill out this form to continue your purchase: {BUY_FORM_URL}"
        send_reply(user_id, reply, platform, respond_voice)
        return

    chat_history = get_chat_history(user_id)

    try:
        intent = detect_intent(message, chat_history)
        db = get_db()
        cursor = db.cursor(dictionary=True)
        data = {}

        if intent == "packages":
            cursor.execute(
                "SELECT name, duration_days, price FROM packages ORDER BY price ASC"
            )
            packages = cursor.fetchall()
            data = {
                "package_count": len(packages),
                "packages": packages,
            }

        elif intent == "package_count":
            cursor.execute("SELECT COUNT(*) AS total FROM packages")
            row = cursor.fetchone()
            data = {"package_count": row["total"]}

        elif intent.startswith("package_price:"):
            package_name = intent.split(":", 1)[1].strip()
            cursor.execute(
                """
                SELECT name, duration_days, price
                FROM packages
                WHERE name LIKE %s
                ORDER BY price ASC
                """,
                (f"%{package_name}%",)
            )
            data = {
                "requested_package": package_name,
                "matches": cursor.fetchall(),
            }

        elif intent == "trainers_count":
            cursor.execute("SELECT COUNT(*) AS total FROM users WHERE role = 'trainer'")
            row = cursor.fetchone()
            data = {"trainers_count": row["total"]}

        elif intent == "clients_count":
            cursor.execute("SELECT COUNT(*) AS total FROM users WHERE role = 'client'")
            row = cursor.fetchone()
            data = {"clients_count": row["total"]}

        elif intent == "bookings_count":
            cursor.execute("SELECT COUNT(*) AS total FROM bookings")
            bookings_row = cursor.fetchone()
            cursor.execute("""
                SELECT status, COUNT(*) AS total
                FROM bookings
                GROUP BY status
            """)
            data = {
                "bookings_count": bookings_row["total"],
                "booking_status_breakdown": cursor.fetchall(),
            }

        elif intent == "payments_total":
            cursor.execute("SELECT COALESCE(SUM(amount), 0) AS total_amount, COUNT(*) AS total_payments FROM payments")
            row = cursor.fetchone()
            data = {
                "payments_total": float(row["total_amount"]),
                "payments_count": row["total_payments"],
            }
        else:
            cursor.execute("SELECT name, duration_days, price FROM packages ORDER BY price ASC")
            packages = cursor.fetchall()
            data = {
                "package_count": len(packages),
                "packages": packages,
                "help": "You can ask about package names, package prices, trainer count, client count, bookings, or payment totals.",
            }
        
        reply = generate_natural_response(message, intent, data, chat_history)
        
        send_reply(user_id, reply, platform, respond_voice)

        save_chat_message(user_id, "user", message)
        save_chat_message(user_id, "assistant", reply)

        cursor.close()
        db.close()

    except Exception as e:
        print(f"Error processing message: {e}")
        error_reply = "I'm sorry, something went wrong while processing your message. Please try again."
        send_reply(user_id, error_reply, platform, respond_voice)


def is_buy_request(message: str) -> bool:
    if not message:
        return False
    return "buy" in message.lower()


def send_facebook_message(recipient_id: str, message_text: str):
    payload = {
        "messaging_type": "RESPONSE",
        "recipient": {"id": recipient_id},
        "message": {"text": message_text}
    }

    params = {"access_token": PAGE_ACCESS_TOKEN}

    try:
        response = requests.post(
            GRAPH_API_URL,
            params=params,
            json=payload
        )
        response.raise_for_status()
    except requests.exceptions.RequestException as e:
        error_msg = getattr(e.response, "text", "No detailed error in response")
        print(f"Error sending Facebook message: {e} - Details: {error_msg}")

    return response.json() if hasattr(response, "content") and response.content else {}


def send_telegram_message(chat_id: str, message_text: str):
    if not TELEGRAM_API_URL:
        print("Error: TELEGRAM_BOT token not set in environment.")
        return {}
        
    payload = {
        "chat_id": chat_id,
        "text": message_text
    }

    try:
        response = requests.post(
            TELEGRAM_API_URL,
            json=payload
        )
        response.raise_for_status()
    except requests.exceptions.RequestException as e:
        error_msg = getattr(e.response, "text", "No detailed error in response")
        print(f"Error sending Telegram message: {e} - Details: {error_msg}")

    return response.json() if hasattr(response, "content") and response.content else {}


def send_telegram_audio(chat_id: str, audio_file_path: str):
    if not TELEGRAM_BOT_TOKEN:
        print("Error: TELEGRAM_BOT token not set in environment.")
        return {}
        
    url = f"https://api.telegram.org/bot{TELEGRAM_BOT_TOKEN}/sendAudio"
    try:
        with open(audio_file_path, 'rb') as audio:
            files = {'audio': audio}
            params = {'chat_id': chat_id}
            response = requests.post(url, params=params, files=files)
            response.raise_for_status()
            return response.json()
    except requests.exceptions.RequestException as e:
        error_msg = getattr(e.response, "text", "No detailed error in response")
        print(f"Error sending Telegram audio: {e} - Details: {error_msg}")
    return {}

def send_facebook_audio(recipient_id: str, audio_file_path: str):
    url = f"https://graph.facebook.com/v18.0/me/messages?access_token={PAGE_ACCESS_TOKEN}"
    data = {
        'recipient': f'{{"id": "{recipient_id}"}}',
        'message': '{"attachment": {"type": "audio", "payload": {"is_reusable": true}}}'
    }
    try:
        with open(audio_file_path, 'rb') as audio_file:
            files = {'filedata': (os.path.basename(audio_file_path), audio_file, 'audio/mpeg')}
            response = requests.post(url, data=data, files=files)
            response.raise_for_status()
            return response.json()
    except requests.exceptions.RequestException as e:
        error_msg = getattr(e.response, "text", "No detailed error in response")
        print(f"Error sending Facebook audio: {e} - Details: {error_msg}")
    return {}
