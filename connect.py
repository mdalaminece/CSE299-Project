from fastapi import FastAPI, Request, Query
from fastapi.responses import PlainTextResponse

app = FastAPI()

VERIFY_TOKEN = "mytoken123"

@app.get('/webhook')
async def verify(
    hub_mode: str = Query(None, alias="hub.mode"),
    hub_verify_token: str = Query(None, alias="hub.verify_token"),
    hub_challenge: str = Query(None, alias="hub.challenge")
):
    if hub_mode == "subscribe" and hub_verify_token == VERIFY_TOKEN:
        return PlainTextResponse(content=hub_challenge)
    return PlainTextResponse(content="Forbidden", status_code=403)

@app.post('/webhook')
async def webhook(request: Request):
    return PlainTextResponse(content="OK")