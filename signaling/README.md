# PHLOURISH call signaling server

Minimal WebSocket server for WebRTC setup between mentee and mentor.
It only relays offers, answers, and ICE candidates — audio and transcript
text never pass through this process.

## Run locally

**Windows (XAMPP):** double-click `signaling/start.bat` and leave that window open.

```bash
cd signaling
npm install
npm start
```

If `npm` is not installed but you have Node:

```bash
cd signaling
node server.js
```

Listens on `ws://localhost:8080` (or `PORT` if set).

Keep this running while using **Call** in the PHP app (`http://localhost/Proyekto/call.php`).

Change the URL in `config/config.php` → `signaling_ws_url` if you deploy the signaling server elsewhere (`wss://...`).
