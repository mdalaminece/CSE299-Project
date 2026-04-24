import os
from pathlib import Path

import mysql.connector
from dotenv import load_dotenv

load_dotenv(dotenv_path=Path(__file__).resolve().parent / ".env")

_schema_initialized = False


def _get_connection_config(include_database=True):
    config = {
        "host": os.getenv("DB_HOST", "localhost"),
        "user": os.getenv("DB_USER", "root"),
        "password": os.getenv("DB_PASSWORD", ""),
    }
    db_port = os.getenv("DB_PORT")
    if db_port:
        config["port"] = int(db_port)
    if include_database:
        config["database"] = os.getenv("DB_NAME")
    return config


def initialize_database():
    global _schema_initialized
    if _schema_initialized:
        return

    db_name = os.getenv("DB_NAME")
    if not db_name:
        raise ValueError("DB_NAME is not set in .env")

    server_conn = mysql.connector.connect(**_get_connection_config(include_database=False))
    server_cursor = server_conn.cursor()
    try:
        server_cursor.execute(f"CREATE DATABASE IF NOT EXISTS `{db_name}`")
    finally:
        server_cursor.close()
        server_conn.close()

    db = mysql.connector.connect(**_get_connection_config())
    cursor = db.cursor()
    try:
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS chat_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(255) NOT NULL,
                role VARCHAR(50) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)

        db.commit()
        _schema_initialized = True
    finally:
        cursor.close()
        db.close()


def get_db():
    initialize_database()
    return mysql.connector.connect(**_get_connection_config())

def save_chat_message(user_id, role, message):
    try:
        db = get_db()
        cursor = db.cursor()
        cursor.execute("INSERT INTO chat_history (user_id, role, message) VALUES (%s, %s, %s)", (str(user_id), role, message))
        db.commit()
        cursor.close()
        db.close()
    except Exception as e:
        print(f"Error saving chat: {e}")

def get_chat_history(user_id, limit=5):
    try:
        db = get_db()
        cursor = db.cursor(dictionary=True)
        cursor.execute("SELECT role, message FROM chat_history WHERE user_id = %s ORDER BY id DESC LIMIT %s", (str(user_id), limit))
        rows = cursor.fetchall()
        cursor.close()
        db.close()
        return rows[::-1] # Return in chronological order
    except Exception as e:
        print(f"Error fetching history: {e}")
        return []


