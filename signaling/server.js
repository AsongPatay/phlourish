// Minimal signaling server for WebRTC calls.
// It never touches audio/video/transcript data — it only relays
// connection setup messages (SDP offers/answers, ICE candidates)
// between two browsers in the same "room".
//
// Run: npm install, then node server.js
// Free to host anywhere that runs Node (Render, Railway free tier,
// Glitch, your own VPS, or even localhost for LAN testing).

const WebSocket = require("ws");

const PORT = process.env.PORT || 8080;
const wss = new WebSocket.Server({ port: PORT });

// roomId -> Set of sockets
const rooms = new Map();

wss.on("connection", (socket) => {
  let currentRoom = null;

  socket.on("message", (raw) => {
    let msg;
    try {
      msg = JSON.parse(raw);
    } catch (e) {
      return; // ignore malformed messages
    }

    if (msg.type === "join") {
      currentRoom = msg.room;
      if (!rooms.has(currentRoom)) rooms.set(currentRoom, new Set());
      const peers = rooms.get(currentRoom);

      if (peers.size >= 2) {
        socket.send(JSON.stringify({ type: "room-full" }));
        return;
      }

      peers.add(socket);
      socket.send(JSON.stringify({ type: "joined", peerCount: peers.size }));

      // tell the other peer (if any) that someone joined
      peers.forEach((peer) => {
        if (peer !== socket) {
          peer.send(JSON.stringify({ type: "peer-joined" }));
        }
      });
      return;
    }

    // relay everything else (offer/answer/ice-candidate) to the other peer in the room
    if (currentRoom && rooms.has(currentRoom)) {
      rooms.get(currentRoom).forEach((peer) => {
        if (peer !== socket && peer.readyState === WebSocket.OPEN) {
          peer.send(raw.toString());
        }
      });
    }
  });

  socket.on("close", () => {
    if (currentRoom && rooms.has(currentRoom)) {
      const peers = rooms.get(currentRoom);
      peers.delete(socket);
      peers.forEach((peer) => peer.send(JSON.stringify({ type: "peer-left" })));
      if (peers.size === 0) rooms.delete(currentRoom);
    }
  });
});

console.log(`Signaling server running on ws://localhost:${PORT}`);
