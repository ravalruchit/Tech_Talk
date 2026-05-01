const express = require('express');
const http    = require('http');
const { Server } = require('socket.io');
const cors    = require('cors');

const app    = express();
const server = http.createServer(app);
const io     = new Server(server, {
  cors: {
    origin: [
      'https://techtalk.gt.tc',
      'http://techtalk.gt.tc',
      'http://localhost',
      'http://127.0.0.1'
    ],
    methods: ['GET', 'POST']
  },
  pingTimeout: 60000,
  pingInterval: 25000
});

app.use(cors());
app.get('/', (req, res) => res.send('TechTalk Signaling Server Running'));

// rooms[roomId] = [socketId, ...]
const rooms = {};
// Mutex to prevent race conditions
const roomLocks = {};

function getRoomSockets(roomId) {
  return rooms[roomId] ? rooms[roomId].filter(id => io.sockets.sockets.has(id)) : [];
}

io.on('connection', socket => {
  console.log('User connected:', socket.id);

  socket.on('join-room', ({ roomId, userName }) => {
    // Prevent race condition with simple lock
    if (roomLocks[roomId]) {
      setTimeout(() => socket.emit('join-room', { roomId, userName }), 100);
      return;
    }
    roomLocks[roomId] = true;

    try {
      // Clean up stale socket IDs
      rooms[roomId] = getRoomSockets(roomId);

      // Remove this socket if already in room (reconnect)
      rooms[roomId] = rooms[roomId].filter(id => id !== socket.id);

      if (rooms[roomId].length >= 2) {
        socket.emit('room-full');
        console.log(`Room ${roomId} is full, rejecting ${userName}`);
        return;
      }

      rooms[roomId].push(socket.id);
      socket.join(roomId);
      socket.roomId   = roomId;
      socket.userName = userName;

      const isInitiator = rooms[roomId].length === 1;
      console.log(`${userName} joined room ${roomId} — isInitiator: ${isInitiator}, total: ${rooms[roomId].length}`);

      socket.emit('room-joined', { roomId, isInitiator, peerCount: rooms[roomId].length });

      // Notify the other person
      socket.to(roomId).emit('peer-joined', { userName, socketId: socket.id });
    } finally {
      // Always release lock
      delete roomLocks[roomId];
    }
  });

  socket.on('offer', ({ roomId, offer }) => {
    console.log(`Offer from ${socket.id} in room ${roomId}`);
    socket.to(roomId).emit('offer', { offer });
  });

  socket.on('answer', ({ roomId, answer }) => {
    console.log(`Answer from ${socket.id} in room ${roomId}`);
    socket.to(roomId).emit('answer', { answer });
  });

  socket.on('ice-candidate', ({ roomId, candidate }) => {
    socket.to(roomId).emit('ice-candidate', { candidate });
  });

  socket.on('report-submitted', ({ roomId, reporterName }) => {
    socket.to(roomId).emit('peer-reported', { reporterName });
  });

  socket.on('disconnect', () => {
    const roomId = socket.roomId;
    if (roomId && rooms[roomId]) {
      rooms[roomId] = rooms[roomId].filter(id => id !== socket.id);
      if (rooms[roomId].length === 0) {
        delete rooms[roomId];
      }
      socket.to(roomId).emit('peer-left', { userName: socket.userName });
      console.log(`${socket.userName} left room ${roomId}, remaining: ${(rooms[roomId] || []).length}`);
    }
    console.log('User disconnected:', socket.id);
  });
});

const PORT = process.env.PORT || 3001;
server.listen(PORT, '0.0.0.0', () => {
  console.log(`TechTalk Signaling Server running on port ${PORT}`);
});
