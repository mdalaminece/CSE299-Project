import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import os
from dotenv import load_dotenv

load_dotenv()

SMTP_SERVER = "smtp.gmail.com"
SMTP_PORT = 587
SENDER_EMAIL = os.getenv("EMAIL_SENDER")
SENDER_PASSWORD = os.getenv("EMAIL_PASSWORD").replace('"', '') # Remove quotes if present

def send_order_email(customer_email, order_details):
    try:
        msg = MIMEMultipart()
        msg['From'] = SENDER_EMAIL
        msg['To'] = customer_email
        msg['Subject'] = "Order Confirmation"

        body = f"""
        Hello {order_details.get('customer_name', 'Customer')},
        
        Thank you for your order!
        
        Order Details:
        Product: {order_details.get('product_name')}
        Quantity: {order_details.get('quantity', 1)}
        Address: {order_details.get('address')}
        
        We will process it shortly.
        """
        
        msg.attach(MIMEText(body, 'plain'))

        server = smtplib.SMTP(SMTP_SERVER, SMTP_PORT)
        server.starttls()
        server.login(SENDER_EMAIL, SENDER_PASSWORD)
        text = msg.as_string()
        server.sendmail(SENDER_EMAIL, customer_email, text)
        server.quit()
        return True
    except Exception as e:
        print(f"Failed to send email: {e}")
        return False
