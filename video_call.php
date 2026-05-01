<?php
require_once 'config.php';
check_login();

$session_id = isset($_GET['session']) ? (int)$_GET['session'] : 0;
if ($session_id <= 0) { header("Location: sessions.php"); exit(); }

$me_email = $_SESSION['email'];
$me = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, Name FROM users WHERE Email = '$me_email'"));
if (!$me) { header("Location: logout.php"); exit(); }
$me_id   = (int)$me['id'];
$me_name = $me['Name'];

// Verify this user belongs to this session
$session = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT s.*, sk.title AS skill_title, t.Name AS teacher_name, l.Name AS learner_name
     FROM sessions s
     JOIN skills sk ON s.skill_id = sk.id
     JOIN users t ON s.teacher_id = t.id
     JOIN users l ON s.learner_id = l.id
     WHERE s.id = $session_id AND (s.teacher_id = $me_id OR s.learner_id = $me_id)"
));

if (!$session) {
    header("Location: sessions.php");
    exit();
}

// Get the other person's info
$is_teacher  = ($session['teacher_id'] == $me_id);
$partner_name = $is_teacher ? $session['learner_name'] : $session['teacher_name'];
$partner_id   = $is_teacher ? $session['learner_id']   : $session['teacher_id'];

$room_id = 'techtalk-session-' . $session_id;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Video Call — <?= htmlspecialchars($session['skill_title']) ?> | TechTalk</title>
  <link rel="stylesheet" href="style.css">
  <style>
    body {
      height: 100vh; overflow: hidden;
      display: flex; flex-direction: column;
      background: #020508;
    }

    /* ── Top bar ── */
    .call-topbar {
      height: 56px; flex-shrink: 0;
      background: rgba(6,11,20,.9);
      backdrop-filter: blur(20px);
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center;
      justify-content: space-between;
      padding: 0 1.5rem; z-index: 10;
    }
    .call-topbar-left { display: flex; align-items: center; gap: .8rem; }
    .call-brand { font-size: 1rem; font-weight: 800; color: var(--text); }
    .call-session-info { font-size: .82rem; color: var(--text-2); }
    .call-timer { font-size: .9rem; font-weight: 700; color: var(--teal); font-variant-numeric: tabular-nums; }

    /* ── Video area ── */
    .video-area {
      flex: 1; position: relative;
      background: #020508; overflow: hidden;
    }

    /* Remote video — full screen */
    #remoteVideo {
      width: 100%; height: 100%;
      object-fit: cover;
      background: #0a0f1a;
    }

    /* Local video — picture-in-picture */
    #localVideo {
      position: absolute;
      bottom: 100px; right: 20px;
      width: 200px; height: 140px;
      border-radius: 12px;
      object-fit: cover;
      border: 2px solid rgba(14,165,233,.4);
      box-shadow: 0 8px 24px rgba(0,0,0,.5);
      cursor: move;
      z-index: 5;
      background: #0a0f1a;
    }

    /* Waiting overlay */
    .waiting-overlay {
      position: absolute; inset: 0;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      background: rgba(2,5,8,.85);
      backdrop-filter: blur(8px);
      z-index: 4;
      gap: 1rem;
    }
    .waiting-overlay.hidden { display: none; }
    .waiting-avatar {
      width: 80px; height: 80px; border-radius: 20px;
      background: var(--grad);
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem; font-weight: 900; color: #fff;
      box-shadow: 0 0 40px rgba(14,165,233,.4);
      animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
      0%, 100% { box-shadow: 0 0 20px rgba(14,165,233,.3); }
      50%       { box-shadow: 0 0 50px rgba(14,165,233,.6); }
    }
    .waiting-text { color: var(--text); font-size: 1.1rem; font-weight: 700; }
    .waiting-sub  { color: var(--text-2); font-size: .875rem; }

    /* ── Controls bar ── */
    .controls {
      position: absolute; bottom: 0; left: 0; right: 0;
      height: 88px;
      background: linear-gradient(to top, rgba(2,5,8,.95), transparent);
      display: flex; align-items: center; justify-content: center;
      gap: 1rem; z-index: 6;
    }

    .ctrl-btn {
      width: 52px; height: 52px; border-radius: 50%;
      border: none; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.2rem; transition: all .2s;
      backdrop-filter: blur(8px);
    }
    .ctrl-btn:hover { transform: scale(1.1); }

    .ctrl-mic   { background: var(--glass-2); border: 1px solid var(--border); color: var(--text); }
    .ctrl-cam   { background: var(--glass-2); border: 1px solid var(--border); color: var(--text); }
    .ctrl-screen{ background: var(--glass-2); border: 1px solid var(--border); color: var(--text); }
    .ctrl-report{ background: rgba(251,191,36,.15); border: 1px solid rgba(251,191,36,.3); color: #fcd34d; }
    .ctrl-end   { background: rgba(248,113,113,.9); border: none; color: #fff; width: 60px; height: 60px; font-size: 1.4rem; box-shadow: 0 4px 20px rgba(248,113,113,.4); }
    .ctrl-end:hover { background: #ef4444; box-shadow: 0 6px 28px rgba(248,113,113,.6); }

    .ctrl-btn.active-off { background: rgba(248,113,113,.2); border-color: rgba(248,113,113,.4); color: #fca5a5; }

    .ctrl-label {
      position: absolute; bottom: -20px; left: 50%; transform: translateX(-50%);
      font-size: .65rem; color: var(--text-3); white-space: nowrap;
    }
    .ctrl-wrap { position: relative; display: flex; flex-direction: column; align-items: center; }

    /* ── Status toast ── */
    .toast {
      position: fixed; top: 70px; left: 50%; transform: translateX(-50%);
      background: rgba(10,18,35,.95); backdrop-filter: blur(16px);
      border: 1px solid var(--border); border-radius: 20px;
      padding: .6rem 1.4rem; font-size: .875rem; color: var(--text);
      z-index: 100; opacity: 0; transition: opacity .3s;
      pointer-events: none;
    }
    .toast.show { opacity: 1; }

    /* ── Report modal ── */
    .report-overlay {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,.7); z-index: 999;
      align-items: center; justify-content: center;
      backdrop-filter: blur(6px);
    }
    .report-overlay.open { display: flex; }
    .report-modal {
      background: rgba(10,18,35,.97); backdrop-filter: blur(24px);
      border: 1px solid rgba(248,113,113,.3);
      border-radius: var(--radius-lg); width: 100%; max-width: 460px;
      box-shadow: 0 24px 60px rgba(0,0,0,.6); overflow: hidden;
    }
    .report-head {
      padding: 1.2rem 1.5rem; border-bottom: 1px solid rgba(248,113,113,.2);
      background: rgba(248,113,113,.06);
      display: flex; justify-content: space-between; align-items: center;
    }
    .report-head h3 { font-size: 1rem; font-weight: 700; color: #fca5a5; }
    .report-close { background: none; border: none; color: var(--text-3); font-size: 1.4rem; cursor: pointer; }
    .report-body { padding: 1.5rem; }
    .report-foot { padding: 1rem 1.5rem; border-top: 1px solid var(--border-2); display: flex; justify-content: flex-end; gap: .6rem; }

    .reason-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .6rem; margin-bottom: 1rem; }
    .reason-btn {
      padding: .6rem .8rem; background: var(--glass);
      border: 1px solid var(--border); border-radius: var(--radius-sm);
      color: var(--text-2); font-size: .82rem; font-weight: 500;
      cursor: pointer; text-align: center; transition: all .15s;
      font-family: var(--font);
    }
    .reason-btn:hover, .reason-btn.selected {
      background: rgba(248,113,113,.12);
      border-color: rgba(248,113,113,.4);
      color: #fca5a5;
    }

    /* Recording indicator */
    .rec-indicator {
      position: absolute; top: 12px; left: 12px;
      display: flex; align-items: center; gap: .4rem;
      background: rgba(248,113,113,.15);
      border: 1px solid rgba(248,113,113,.3);
      border-radius: 20px; padding: .3rem .8rem;
      font-size: .75rem; font-weight: 700; color: #fca5a5;
      z-index: 5;
    }
    .rec-dot {
      width: 8px; height: 8px; border-radius: 50%;
      background: #f87171;
      animation: blink 1.2s ease-in-out infinite;
    }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
  </style>
</head>
<body>

<div class="mesh-bg" style="opacity:.3;">
  <div class="blob blob-1"></div>
  <div class="blob blob-2"></div>
</div>

<!-- Top bar -->
<div class="call-topbar">
  <div class="call-topbar-left">
    <span class="call-brand">🌟 TechTalk</span>
    <span class="call-session-info">
      <?= htmlspecialchars($session['skill_title']) ?> &middot; with <?= htmlspecialchars($partner_name) ?>
    </span>
  </div>
  <div class="call-timer" id="callTimer">00:00</div>
</div>

<!-- Video area -->
<div class="video-area">

  <!-- Recording indicator -->
  <div class="rec-indicator" id="recIndicator" style="display:none;">
    <div class="rec-dot"></div> REC
  </div>

  <!-- Remote video -->
  <video id="remoteVideo" autoplay playsinline></video>

  <!-- Local video (PiP) -->
  <video id="localVideo" autoplay playsinline muted></video>

  <!-- Waiting overlay -->
  <div class="waiting-overlay" id="waitingOverlay">
    <div class="waiting-avatar"><?= strtoupper(substr($partner_name, 0, 1)) ?></div>
    <div class="waiting-text">Waiting for <?= htmlspecialchars($partner_name) ?>...</div>
    <div class="waiting-sub">Share your session link or wait for them to join</div>
  </div>

  <!-- Controls -->
  <div class="controls">
    <div class="ctrl-wrap">
      <button class="ctrl-btn ctrl-mic" id="btnMic" onclick="toggleMic()" title="Mute/Unmute">🎤</button>
      <span class="ctrl-label">Mute</span>
    </div>
    <div class="ctrl-wrap">
      <button class="ctrl-btn ctrl-cam" id="btnCam" onclick="toggleCam()" title="Camera on/off">📷</button>
      <span class="ctrl-label">Camera</span>
    </div>
    <div class="ctrl-wrap">
      <button class="ctrl-btn ctrl-screen" id="btnScreen" onclick="toggleScreen()" title="Share screen">🖥️</button>
      <span class="ctrl-label">Screen</span>
    </div>
    <div class="ctrl-wrap">
      <button class="ctrl-btn ctrl-report" onclick="openReport()" title="Report">🚩</button>
      <span class="ctrl-label">Report</span>
    </div>
    <div class="ctrl-wrap">
      <button class="ctrl-btn ctrl-end" onclick="endCall()" title="End call">📵</button>
      <span class="ctrl-label">End</span>
    </div>
  </div>

</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<!-- Report Modal -->
<div class="report-overlay" id="reportOverlay">
  <div class="report-modal">
    <div class="report-head">
      <h3>🚩 Report User</h3>
      <button class="report-close" onclick="closeReport()">✕</button>
    </div>
    <form id="reportForm">
      <div class="report-body">
        <div class="form-group">
          <label class="form-label">Reason *</label>
          <div class="reason-grid">
            <button type="button" class="reason-btn" data-reason="Harassment">😡 Harassment</button>
            <button type="button" class="reason-btn" data-reason="Inappropriate content">🔞 Inappropriate</button>
            <button type="button" class="reason-btn" data-reason="No-show / not teaching">👻 No-show</button>
            <button type="button" class="reason-btn" data-reason="Fake skill">🎭 Fake skill</button>
            <button type="button" class="reason-btn" data-reason="Abusive language">🤬 Abusive</button>
            <button type="button" class="reason-btn" data-reason="Other">❓ Other</button>
          </div>
          <input type="hidden" id="selectedReason" value="">
        </div>
        <div class="form-group">
          <label class="form-label">Additional details</label>
          <textarea class="form-textarea" id="reportDesc" placeholder="Describe what happened..." style="min-height:90px;"></textarea>
        </div>
        <div style="background:rgba(251,191,36,.08);border:1px solid rgba(251,191,36,.2);border-radius:8px;padding:.8rem 1rem;font-size:.82rem;color:#fcd34d;">
          ⚠️ This session is being recorded. Your report will be reviewed by our admin team along with the recording.
        </div>
      </div>
      <div class="report-foot">
        <button type="button" class="btn btn-glass" onclick="closeReport()">Cancel</button>
        <button type="button" class="btn btn-danger" onclick="submitReport()">Submit Report</button>
      </div>
    </form>
  </div>
</div>

<!-- Socket.io client -->
<script>
  // ── HTTPS check for mobile/remote access ──
  if (window.location.hostname !== 'localhost' && window.location.protocol !== 'https:') {
    const banner = document.createElement('div');
    banner.style.cssText = 'position:fixed;top:56px;left:0;right:0;background:#7f1d1d;color:#fca5a5;text-align:center;padding:.75rem 1rem;font-size:.875rem;font-weight:600;z-index:999;border-bottom:1px solid rgba(248,113,113,.4);';
    banner.innerHTML = '⚠️ Video calls require HTTPS when accessed from another device. Camera/mic will be blocked by the browser.';
    document.body.appendChild(banner);
  }

  // Signaling server URL
  const SIGNAL_SERVER = '<?= getenv("SIGNAL_SERVER_URL") ?: "https://techtalk-signal.onrender.com" ?>';

  // Load socket.io then start the call
  const s = document.createElement('script');
  s.src = SIGNAL_SERVER + '/socket.io/socket.io.js';
  s.onload = () => init();
  s.onerror = () => {
    // Signaling server not reachable — still start camera/mic
    console.warn('Signaling server not reachable, starting local only');
    init();
  };
  document.head.appendChild(s);
</script>

<script>
// ─── Config ──────────────────────────────────────────────────────────────────
const ROOM_ID    = '<?= $room_id ?>';
const MY_NAME    = '<?= addslashes($me_name) ?>';
const SESSION_ID = <?= $session_id ?>;
const ME_ID      = <?= $me_id ?>;
const PARTNER_ID = <?= $partner_id ?>;

// ─── State ───────────────────────────────────────────────────────────────────
let localStream    = null;
let peerConnection = null;
let socket         = null;
let isInitiator    = false;
let micOn          = true;
let camOn          = true;
let screenSharing  = false;
let mediaRecorder  = null;
let recordedChunks = [];
let callStartTime  = null;
let timerInterval  = null;
let reportSubmitted = false; // ✅ Only upload recording if a report was filed

const iceServers = {
  iceServers: [
    { urls: 'stun:stun.l.google.com:19302' },
    { urls: 'stun:stun1.l.google.com:19302' },
    { urls: 'stun:stun2.l.google.com:19302' },
    {
      urls: 'turn:openrelay.metered.ca:80',
      username: 'openrelayproject',
      credential: 'openrelayproject'
    },
    {
      urls: 'turn:openrelay.metered.ca:443',
      username: 'openrelayproject',
      credential: 'openrelayproject'
    },
    {
      urls: 'turn:openrelay.metered.ca:443?transport=tcp',
      username: 'openrelayproject',
      credential: 'openrelayproject'
    }
  ]
};

// ─── Init ─────────────────────────────────────────────────────────────────────
async function init() {
  try {
    // Try with both video and audio first
    localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
    document.getElementById('localVideo').srcObject = localStream;
    connectSocket();
  } catch (err) {
    console.error('getUserMedia error:', err.name, err.message);

    // Camera busy (e.g. another tab has it) — try video-only fallback first, then audio-only
    if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
      try {
        localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        document.getElementById('localVideo').srcObject = localStream;
        showToast('⚠️ Mic busy — video only mode');
        connectSocket();
        return;
      } catch (e) { /* fall through to audio-only */ }

      try {
        localStream = await navigator.mediaDevices.getUserMedia({ video: false, audio: true });
        document.getElementById('localVideo').srcObject = localStream;
        showToast('⚠️ Camera busy — audio only mode');
        connectSocket();
        return;
      } catch (e) { /* fall through to error */ }
    }

    // NotAllowedError on mobile via HTTP (non-localhost) — getUserMedia requires HTTPS
    if (err.name === 'NotAllowedError' && window.location.hostname !== 'localhost') {
      showError('❌ On mobile, video calls require HTTPS. Ask your admin to enable SSL, or test on the same PC using localhost.');
      return;
    }

    let msg = '❌ Camera/mic access denied. Please allow permissions.';
    if (err.name === 'NotReadableError') {
      msg = '❌ Camera is in use by another tab or app. Close other tabs and retry.';
    } else if (err.name === 'NotFoundError') {
      msg = '❌ No camera/mic found on this device.';
    } else if (err.name === 'NotAllowedError') {
      msg = '❌ Permission denied. Click the camera icon in the address bar to allow.';
    } else if (err.name === 'OverconstrainedError') {
      msg = '❌ Camera constraints not supported on this device.';
    }

    showError(msg);
  }
}

function showError(msg) {
  showToast(msg);
  // Remove any existing retry button first
  const existing = document.getElementById('retryBtn');
  if (existing) existing.remove();

  const retryBtn = document.createElement('button');
  retryBtn.id = 'retryBtn';
  retryBtn.className = 'btn btn-primary';
  retryBtn.style.cssText = 'position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:100;';
  retryBtn.textContent = '🔄 Retry Camera Access';
  retryBtn.onclick = () => { retryBtn.remove(); init(); };
  document.body.appendChild(retryBtn);
}

// ─── Socket ──────────────────────────────────────────────────────────────────
function connectSocket() {
  socket = io(SIGNAL_SERVER, { transports: ['websocket', 'polling'] });

  socket.on('connect', () => {
    console.log('Socket connected:', socket.id);
    socket.emit('join-room', { roomId: ROOM_ID, userName: MY_NAME });
  });

  socket.on('connect_error', (err) => {
    console.error('Socket connection error:', err.message);
    showToast('❌ Cannot reach signaling server. Is node server.js running?');
  });

  socket.on('room-joined', ({ isInitiator: init, peerCount }) => {
    isInitiator = init;
    console.log(`Joined room — isInitiator: ${isInitiator}, peers: ${peerCount}`);

    // If 2 people already in room (rejoining), initiate immediately
    if (peerCount === 2 && !isInitiator) {
      createPeerConnection();
      createOffer();
    }
  });

  socket.on('peer-joined', ({ userName }) => {
    showToast(`✅ ${userName} joined the call`);
    document.getElementById('waitingOverlay').classList.add('hidden');
    startCallTimer();
    if (isInitiator) {
      // Close any old connection first
      if (peerConnection) { peerConnection.close(); peerConnection = null; }
      createPeerConnection();
      createOffer();
    }
  });

  socket.on('offer', async ({ offer }) => {
    console.log('Received offer');
    try {
      if (!peerConnection) createPeerConnection();
      // Use plain object — RTCSessionDescription constructor is deprecated
      await peerConnection.setRemoteDescription(offer);
      const answer = await peerConnection.createAnswer();
      await peerConnection.setLocalDescription(answer);
      socket.emit('answer', { roomId: ROOM_ID, answer });
      console.log('Answer sent');
    } catch (err) {
      console.error('Error handling offer:', err);
      // Retry once after short delay
      setTimeout(async () => {
        try {
          if (peerConnection && peerConnection.signalingState !== 'closed') {
            const answer = await peerConnection.createAnswer();
            await peerConnection.setLocalDescription(answer);
            socket.emit('answer', { roomId: ROOM_ID, answer });
          }
        } catch(e) { console.error('Retry failed:', e); }
      }, 1000);
    }
  });

  socket.on('answer', async ({ answer }) => {
    console.log('Received answer');
    try {
      if (peerConnection && peerConnection.signalingState !== 'closed') {
        // Use plain object — RTCSessionDescription constructor is deprecated
        await peerConnection.setRemoteDescription(answer);
      }
    } catch (err) {
      console.error('Error handling answer:', err);
    }
  });

  socket.on('ice-candidate', async ({ candidate }) => {
    try {
      if (peerConnection && candidate) {
        await peerConnection.addIceCandidate(new RTCIceCandidate(candidate));
      }
    } catch (err) {
      console.warn('ICE candidate error (safe to ignore):', err.message);
    }
  });

  socket.on('peer-left', ({ userName }) => {
    showToast(`${userName} left the call`);
    document.getElementById('remoteVideo').srcObject = null;
    document.getElementById('waitingOverlay').classList.remove('hidden');
    stopCallTimer();
    if (peerConnection) { peerConnection.close(); peerConnection = null; }
  });

  socket.on('room-full', () => {
    showToast('❌ Room is full. Only 2 people allowed per session.');
  });

  socket.on('disconnect', (reason) => {
    console.warn('Socket disconnected:', reason);
    if (reason === 'io server disconnect') {
      // Server kicked us, try reconnect
      socket.connect();
    }
  });
}

// ─── WebRTC ──────────────────────────────────────────────────────────────────
function createPeerConnection() {
  peerConnection = new RTCPeerConnection(iceServers);
  console.log('PeerConnection created');

  // Add local tracks
  localStream.getTracks().forEach(track => {
    peerConnection.addTrack(track, localStream);
  });

  // Receive remote stream
  peerConnection.ontrack = event => {
    console.log('Remote track received');
    document.getElementById('remoteVideo').srcObject = event.streams[0];
    document.getElementById('waitingOverlay').classList.add('hidden');
    startCallTimer();
    startRecording(); // ✅ Start recording only when both users are connected
  };

  // Send ICE candidates
  peerConnection.onicecandidate = event => {
    if (event.candidate) {
      socket.emit('ice-candidate', { roomId: ROOM_ID, candidate: event.candidate });
    }
  };

  peerConnection.oniceconnectionstatechange = () => {
    console.log('ICE state:', peerConnection.iceConnectionState);
    if (peerConnection.iceConnectionState === 'failed') {
      showToast('⚠️ Connection failed. Retrying...');
      peerConnection.restartIce();
      // Re-send offer after ICE restart if we are initiator
      if (isInitiator) {
        setTimeout(async () => {
          try {
            const offer = await peerConnection.createOffer({ iceRestart: true });
            await peerConnection.setLocalDescription(offer);
            socket.emit('offer', { roomId: ROOM_ID, offer });
          } catch(e) { console.error('ICE restart offer failed:', e); }
        }, 500);
      }
    }
    if (peerConnection.iceConnectionState === 'disconnected') {
      showToast('⚠️ Connection lost. Waiting to reconnect...');
    }
    if (peerConnection.iceConnectionState === 'connected') {
      showToast('✅ Connected!');
    }
  };

  peerConnection.onconnectionstatechange = () => {
    console.log('Connection state:', peerConnection.connectionState);
  };
}

async function createOffer() {
  const offer = await peerConnection.createOffer();
  await peerConnection.setLocalDescription(offer);
  socket.emit('offer', { roomId: ROOM_ID, offer });
}

// ─── Controls ────────────────────────────────────────────────────────────────
function toggleMic() {
  if (!localStream) { showToast('⚠️ No microphone available'); return; }
  micOn = !micOn;
  localStream.getAudioTracks().forEach(t => t.enabled = micOn);
  const btn = document.getElementById('btnMic');
  btn.textContent = micOn ? '🎤' : '🔇';
  btn.classList.toggle('active-off', !micOn);
  showToast(micOn ? 'Microphone on' : 'Microphone muted');
}

function toggleCam() {
  if (!localStream) { showToast('⚠️ No camera available'); return; }
  camOn = !camOn;
  localStream.getVideoTracks().forEach(t => t.enabled = camOn);
  const btn = document.getElementById('btnCam');
  btn.textContent = camOn ? '📷' : '🚫';
  btn.classList.toggle('active-off', !camOn);
  showToast(camOn ? 'Camera on' : 'Camera off');
}

async function toggleScreen() {
  if (!screenSharing) {
    try {
      const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
      const screenTrack  = screenStream.getVideoTracks()[0];

      // Replace video track in peer connection if connected
      if (peerConnection) {
        const sender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
        if (sender) sender.replaceTrack(screenTrack);
      }

      document.getElementById('localVideo').srcObject = screenStream;
      screenSharing = true;
      document.getElementById('btnScreen').classList.add('active-off');
      showToast('Screen sharing started');

      screenTrack.onended = () => stopScreen();
    } catch (err) {
      showToast('Screen share cancelled');
    }
  } else {
    stopScreen();
  }
}

async function stopScreen() {
  const videoTrack = localStream.getVideoTracks()[0];
  if (peerConnection) {
    const sender = peerConnection.getSenders().find(s => s.track && s.track.kind === 'video');
    if (sender) sender.replaceTrack(videoTrack);
  }
  document.getElementById('localVideo').srcObject = localStream;
  screenSharing = false;
  document.getElementById('btnScreen').classList.remove('active-off');
  showToast('Screen sharing stopped');
}

function endCall() {
  if (!confirm('End this video call?')) return;

  // Stop all connections
  if (peerConnection) peerConnection.close();
  if (socket) socket.disconnect();
  if (localStream) localStream.getTracks().forEach(t => t.stop());
  stopCallTimer();

  // Only upload recording if a report was submitted — saves storage
  if (reportSubmitted && mediaRecorder && mediaRecorder.state !== 'inactive') {
    showToast('⏳ Saving report evidence, please wait...');
    mediaRecorder.onstop = () => uploadRecordingThenRedirect();
    mediaRecorder.stop();
  } else {
    // No report — discard recording, just redirect
    recordedChunks = [];
    window.location.href = 'sessions.php';
  }
}

function uploadRecordingThenRedirect() {
  if (recordedChunks.length === 0) {
    console.warn('No chunks — redirecting');
    window.location.href = 'sessions.php';
    return;
  }

  const blob = new Blob(recordedChunks, { type: mediaRecorder.mimeType || 'video/webm' });
  console.log('Uploading recording:', (blob.size / 1024 / 1024).toFixed(2), 'MB');

  const formData = new FormData();
  formData.append('recording', blob, `session_${SESSION_ID}_${Date.now()}.webm`);
  formData.append('session_id', SESSION_ID);
  formData.append('recorder_id', ME_ID);

  fetch('save_recording.php', { method: 'POST', body: formData })
    .then(r => {
      if (!r.ok) throw new Error('HTTP ' + r.status);
      return r.json();
    })
    .then(data => {
      console.log('Recording result:', data);
      if (data.success) {
        showToast('✅ Recording saved!');
      } else {
        console.error('Save failed:', data.error);
      }
    })
    .catch(err => console.error('Upload error:', err))
    .finally(() => {
      setTimeout(() => { window.location.href = 'sessions.php'; }, 1000);
    });
}

// ─── Recording ───────────────────────────────────────────────────────────────
function startRecording() {
  // Guard — only start once
  if (mediaRecorder && mediaRecorder.state !== 'inactive') return;
  try {
    // Find a supported mime type
    const mimeTypes = [
      'video/webm;codecs=vp9,opus',
      'video/webm;codecs=vp8,opus',
      'video/webm;codecs=h264,opus',
      'video/webm',
      'video/mp4'
    ];
    let mimeType = '';
    for (const type of mimeTypes) {
      if (MediaRecorder.isTypeSupported(type)) {
        mimeType = type;
        break;
      }
    }

    if (!mimeType) {
      console.warn('No supported recording format found');
      showToast('⚠️ Recording not supported on this browser');
      return;
    }

    console.log('Recording with format:', mimeType);
    mediaRecorder = new MediaRecorder(localStream, { mimeType });
    recordedChunks = [];

    mediaRecorder.ondataavailable = e => {
      if (e.data && e.data.size > 0) {
        recordedChunks.push(e.data);
        console.log('Chunk recorded, size:', e.data.size);
      }
    };

    mediaRecorder.onerror = e => {
      console.error('MediaRecorder error:', e);
    };

    mediaRecorder.start(2000); // collect every 2 seconds
    document.getElementById('recIndicator').style.display = 'flex';
    console.log('Recording started');
  } catch (err) {
    console.error('Recording setup failed:', err);
    showToast('⚠️ Could not start recording: ' + err.message);
  }
}

function stopRecording() {
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop();
    document.getElementById('recIndicator').style.display = 'none';
  }
}

function uploadRecording() {
  if (recordedChunks.length === 0) {
    console.warn('No recorded chunks to upload');
    return;
  }

  const blob = new Blob(recordedChunks, { type: mediaRecorder.mimeType || 'video/webm' });
  console.log('Uploading recording, size:', (blob.size / 1024 / 1024).toFixed(2), 'MB');

  // If file is too large (>20MB), warn but still try
  if (blob.size > 20 * 1024 * 1024) {
    console.warn('Large recording file:', blob.size, 'bytes');
  }

  const formData = new FormData();
  formData.append('recording', blob, `session_${SESSION_ID}_${Date.now()}.webm`);
  formData.append('session_id', SESSION_ID);
  formData.append('recorder_id', ME_ID);

  showToast('⏳ Saving recording...');

  fetch('save_recording.php', {
    method: 'POST',
    body: formData
  })
  .then(r => {
    if (!r.ok) throw new Error('HTTP ' + r.status);
    return r.json();
  })
  .then(data => {
    console.log('Recording saved:', data);
    if (data.success) {
      showToast('✅ Recording saved successfully');
    } else {
      console.error('Save failed:', data.error);
      showToast('⚠️ Recording save failed: ' + (data.error || 'unknown error'));
    }
  })
  .catch(err => {
    console.error('Upload failed:', err);
    showToast('⚠️ Upload failed: ' + err.message);
  });
}

// ─── Timer ───────────────────────────────────────────────────────────────────
function startCallTimer() {
  if (callStartTime) return;
  callStartTime = Date.now();
  timerInterval = setInterval(() => {
    const elapsed = Math.floor((Date.now() - callStartTime) / 1000);
    const m = String(Math.floor(elapsed / 60)).padStart(2, '0');
    const s = String(elapsed % 60).padStart(2, '0');
    document.getElementById('callTimer').textContent = `${m}:${s}`;
  }, 1000);
}

function stopCallTimer() {
  clearInterval(timerInterval);
}

// ─── Report ──────────────────────────────────────────────────────────────────
function openReport() {
  document.getElementById('reportOverlay').classList.add('open');
}
function closeReport() {
  document.getElementById('reportOverlay').classList.remove('open');
}

document.querySelectorAll('.reason-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.reason-btn').forEach(b => b.classList.remove('selected'));
    this.classList.add('selected');
    document.getElementById('selectedReason').value = this.getAttribute('data-reason');
  });
});

function submitReport() {
  const reason = document.getElementById('selectedReason').value;
  const desc   = document.getElementById('reportDesc').value.trim();

  if (!reason) { showToast('⚠️ Please select a reason'); return; }

  fetch('submit_report.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      session_id:  SESSION_ID,
      reporter_id: ME_ID,
      reported_id: PARTNER_ID,
      reason,
      description: desc
    })
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      closeReport();
      showToast('✅ Report submitted. Our team will review it.');
      reportSubmitted = true;
      // Upload recording immediately as evidence
      if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.onstop = () => uploadRecording();
        mediaRecorder.stop();
      } else if (recordedChunks.length > 0) {
        uploadRecording();
      }
      if (socket) socket.emit('report-submitted', { roomId: ROOM_ID, reporterName: MY_NAME });
    }
  })
  .catch(() => showToast('❌ Failed to submit report'));
}

// ─── Toast ───────────────────────────────────────────────────────────────────
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

// ─── Draggable local video ────────────────────────────────────────────────────
(function() {
  const el = document.getElementById('localVideo');
  let dragging = false, ox = 0, oy = 0;
  el.addEventListener('mousedown', e => {
    dragging = true;
    ox = e.clientX - el.getBoundingClientRect().left;
    oy = e.clientY - el.getBoundingClientRect().top;
  });
  document.addEventListener('mousemove', e => {
    if (!dragging) return;
    el.style.right = 'auto';
    el.style.bottom = 'auto';
    el.style.left = (e.clientX - ox) + 'px';
    el.style.top  = (e.clientY - oy) + 'px';
  });
  document.addEventListener('mouseup', () => dragging = false);
})();

// Save recording only if a report was filed and user closes tab
window.addEventListener('beforeunload', (e) => {
  if (reportSubmitted && mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.onstop = () => {
      if (recordedChunks.length === 0) return;
      const blob = new Blob(recordedChunks, { type: mediaRecorder.mimeType || 'video/webm' });
      const formData = new FormData();
      formData.append('recording', blob, `session_${SESSION_ID}_${Date.now()}.webm`);
      formData.append('session_id', SESSION_ID);
      formData.append('recorder_id', ME_ID);
      navigator.sendBeacon('save_recording.php', formData);
    };
    mediaRecorder.stop();
  }
});
</script>
</body>
</html>
