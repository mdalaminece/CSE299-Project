import os
import requests
import uvicorn
from dotenv import load_dotenv

def main():
    load_dotenv()
    TELEGRAM_BOT_TOKEN = os.getenv("TELEGRAM_BOT")
    
    if not TELEGRAM_BOT_TOKEN:
        print("❌ TELEGRAM_BOT token is missing in .env file.")
        return

    print("=========================================================")
    print("🤖 Telegram & Facebook Bot Launcher")
    print("=========================================================")
    print("Telegram er message thik moto receive korar jonno ngrok URL dorkar.")
    print("Apnar ngrok er terminal theke HTTPS link ta ekhane din.")
    print("Example: https://e2b4-xyz.ngrok-free.app")
    
    ngrok_url = input("Enter your ngrok HTTPS URL: ").strip()

    if ngrok_url:
        if ngrok_url.endswith("/"):
            ngrok_url = ngrok_url[:-1]
        
        webhook_url = f"{ngrok_url}/telegram-webhook"
        api_url = f"https://api.telegram.org/bot{TELEGRAM_BOT_TOKEN}/setWebhook?url={webhook_url}"
        
        print("\nConnecting Telegram webhook to your server...")
        try:
            response = requests.get(api_url).json()
            if response.get("ok"):
                print(f"✅ Telegram Webhook successfully connected to: {webhook_url}")
                print("Ekhon Telegram eo reply asbe, Facebook eo asbe!\n")
            else:
                print(f"❌ Failed to set webhook: {response.get('description')}")
        except Exception as e:
            print(f"❌ Error connecting to Telegram API: {e}")
    else:
        print("Skipping Telegram webhook setup.")

    print("\n🚀 Starting the FastAPI server for BOTH Facebook and Telegram...")
    # uvicorn run command starting the server on port 8000
    uvicorn.run("main:app", host="0.0.0.0", port=8000, reload=True)

if __name__ == "__main__":
    main()
