from groq import Groq
import os
from pathlib import Path
from dotenv import load_dotenv

load_dotenv(dotenv_path=Path(__file__).resolve().parent / ".env")

client = Groq(api_key=os.getenv("GROQ_API_KEY"))

SYSTEM_PROMPT = """
You are a gym management assistant and a health & fitness expert.
User may ask about gym packages, prices, trainers, clients, bookings, gym information, or health and fitness related questions.
Decide intent as one of:
- packages (asking to list packages or package details)
- package_count (asking how many packages are available)
- package_price:<package_name> (asking the price of a specific package)
- trainers_count (asking how many trainers there are)
- clients_count (asking how many clients there are)
- bookings_count (asking how many bookings/sessions there are)
- payments_total (asking about payment totals or revenue recorded in DB)
- health_fitness_qa (asking anything related to workout, exercise, diet, nutrition, fitness tips, etc.)
- unknown
Respond ONLY with intent.
"""

def detect_intent(user_message, chat_history=[]):
    messages = [{"role": "system", "content": SYSTEM_PROMPT}]
    
    # Add history
    for msg in chat_history:
        role = "user" if msg['role'] == "user" else "assistant"
        messages.append({"role": role, "content": msg['message']})
        
    messages.append({"role": "user", "content": user_message})
    
    response = client.chat.completions.create(
        model=os.getenv("MODEL_NAME", "llama3-8b-8192"), # Fallback model
        messages=messages
    )
    return response.choices[0].message.content.strip().lower()

def generate_natural_response(user_message, intent, db_data, chat_history=[]):
    """
    Generates a natural language response using the LLM, incorporating database data.
    """
    system_prompt = """
    You are a friendly and professional gym assistant and health & fitness expert. 
    Your goal is to answer the user's question using the provided context or your fitness knowledge.
    
    - If the intent is related to gym data, use only the provided database context for factual claims.
    - If package data is provided, mention names, durations, and prices clearly.
    - If the intent is 'health_fitness_qa', creatively provide useful, accurate, and encouraging health, exercise, or nutrition advice.
    - If no matching record is found, say that clearly and briefly.
    - If the intent was 'unknown' or chatty, answer briefly and mention available package help when helpful.
    - Keep responses concise and natural.
    """
    
    context_str = f"Intent: {intent}\nData Found: {str(db_data)}"
    
    messages = [{"role": "system", "content": system_prompt}]
    
    # Add history
    for msg in chat_history:
        role = "user" if msg['role'] == "user" else "assistant"
        messages.append({"role": role, "content": msg['message']})
        
    messages.append({"role": "system", "content": f"Context:\n{context_str}"})
    messages.append({"role": "user", "content": user_message})
    
    response = client.chat.completions.create(
        model=os.getenv("MODEL_NAME", "llama3-8b-8192"),
        messages=messages
    )
    return response.choices[0].message.content.strip()

def transcribe_audio(file_path):
    """
    Transcribes an audio file using Groq's whisper model.
    """
    try:
        with open(file_path, "rb") as file:
            transcription = client.audio.transcriptions.create(
              file=(file_path, file.read()),
              model="whisper-large-v3-turbo",
              language="en"
            )
            return transcription.text.strip()
    except Exception as e:
        print(f"Error transcribing audio: {e}")
        return ""

