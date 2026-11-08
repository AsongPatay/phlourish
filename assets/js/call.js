(function () {
  var root = document.getElementById("call-root");
  if (!root) return;

  var csrf = root.getAttribute("data-csrf");
  var role = root.getAttribute("data-role");
  var displayName = root.getAttribute("data-name") || "User";
  var signalingUrl = root.getAttribute("data-signaling") || "ws://localhost:8080";
  var peerLabel = root.getAttribute("data-peer-label") || "Peer";
  var statusEl = document.getElementById("call-status");
  var captions = document.getElementById("call-captions");
  var captionsEmpty = document.getElementById("call-captions-empty");
  var remoteAudio = document.getElementById("remote-audio");
  var startBtn = document.getElementById("btn-start");
  var joinBtn = document.getElementById("btn-join");
  var endBtn = document.getElementById("btn-end");
  var muteBtn = document.getElementById("btn-mute");
  var downloadBtn = document.getElementById("btn-download");
  var langEl = document.getElementById("call-language");
  var pickEl = document.getElementById("call-pick");
  var joinWrap = document.getElementById("call-join-wrap");
  var waitingEl = document.getElementById("call-waiting");
  var badgeEl = document.getElementById("call-conn-badge");
  var timerEl = document.getElementById("call-timer");
  var peerAvatar = document.getElementById("call-peer-avatar");
  var selfAvatar = document.getElementById("call-self-avatar");
  var peerNameEl = document.getElementById("call-peer-name");
  var peerInitialsEl = document.getElementById("call-peer-initials");
  var inboxList = document.getElementById("call-inbox-list");
  var inboxCount = document.getElementById("call-inbox-count");
  var inboxAside = document.querySelector(".call-inbox");

  var callId = 0;
  var lastLine = 0;
  var polling = null;
  var openCallsPolling = null;
  var transcriptLines = [];
  var isMuted = false;
  var inCall = false;
  var isOfferer = false;
  var timerStartedAt = 0;
  var timerInterval = null;

  var ws = null;
  var pc = null;
  var dataChannel = null;
  var localStream = null;
  var voskAudioContext = null;
  var voskRecognizerNode = null;
  var voskSource = null;
  var voskGain = null;
  var voskRecognizer = null;
  var voskStarting = false;
  var voskModelCache = {};
  var callLanguage = "english";

  var MODEL_URLS = {
    english: "assets/models/vosk-model-en-us-0.22-lgraph.tar.gz",
    filipino: "assets/models/vosk-model-tl-ph-generic-0.6.tar.gz",
    taglish: "assets/models/vosk-model-en-us-0.22-lgraph.tar.gz"
  };

  var STUN = { iceServers: [{ urls: "stun:stun.l.google.com:19302" }] };

  function post(action, extra) {
    var body = new FormData();
    body.append("csrf_token", csrf);
    body.append("action", action);
    if (callId) body.append("call_id", String(callId));
    Object.keys(extra || {}).forEach(function (key) {
      body.append(key, extra[key]);
    });
    return fetch("call.php?ajax=1", { method: "POST", body: body, credentials: "same-origin" }).then(function (res) {
      return res.json();
    });
  }

  function setStatus(msg) {
    statusEl.textContent = msg;
  }

  function setBadge(label, state) {
    if (!badgeEl) return;
    badgeEl.textContent = label;
    if (state) badgeEl.setAttribute("data-state", state);
    else badgeEl.removeAttribute("data-state");
  }

  function setAvatarState(el, state) {
    if (el) el.setAttribute("data-state", state || "idle");
  }

  function startTimer() {
    timerStartedAt = Date.now();
    if (timerEl) timerEl.hidden = false;
    if (timerInterval) window.clearInterval(timerInterval);
    timerInterval = window.setInterval(function () {
      if (!timerEl) return;
      var sec = Math.floor((Date.now() - timerStartedAt) / 1000);
      var m = String(Math.floor(sec / 60)).padStart(2, "0");
      var s = String(sec % 60).padStart(2, "0");
      timerEl.textContent = m + ":" + s;
    }, 500);
  }

  function stopTimer() {
    if (timerInterval) {
      window.clearInterval(timerInterval);
      timerInterval = null;
    }
  }

  function roomCode() {
    return "phlourish-call-" + callId;
  }

  function addCaption(who, text, mine) {
    if (captionsEmpty) captionsEmpty.hidden = true;
    var time = new Date().toLocaleTimeString();
    transcriptLines.push({ who: who, text: text, time: time });
    var p = document.createElement("p");
    p.className = mine ? "call-line call-line--me" : "call-line call-line--them";
    p.innerHTML =
      '<span class="call-line__ts">[' + time + "]</span> " +
      "<b>" + escapeHtml(who) + ":</b> " +
      escapeHtml(text);
    captions.appendChild(p);
    captions.scrollTop = captions.scrollHeight;
    if (downloadBtn && inCall) downloadBtn.disabled = false;
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function poll() {
    if (!callId) return;
    fetch("call.php?ajax=1&action=poll&call_id=" + callId + "&after=" + lastLine, { credentials: "same-origin" })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.ok) return;
        (data.lines || []).forEach(function (line) {
          lastLine = line.id;
        });
        if (data.status === "ended" && inCall) {
          setStatus("The other person ended the call. Transcript saved.");
          setBadge("Ended", "idle");
          setAvatarState(peerAvatar, "idle");
          setAvatarState(selfAvatar, "idle");
          stopTimer();
          teardownMedia(false);
          if (endBtn) endBtn.hidden = true;
          if (muteBtn) muteBtn.hidden = true;
          if (downloadBtn) {
            downloadBtn.hidden = false;
            downloadBtn.disabled = transcriptLines.length === 0;
          }
        }
      })
      .catch(function () {});
  }

  function initials(name) {
    var parts = String(name || "").trim().split(/\s+/).filter(Boolean);
    var out = parts.slice(0, 2).map(function (p) { return p.charAt(0).toUpperCase(); }).join("");
    return out || "??";
  }

  function selectInboxItem(btn) {
    if (!btn || !pickEl) return;
    var id = btn.getAttribute("data-call-id");
    var lang = btn.getAttribute("data-language") || "english";
    var name = btn.getAttribute("data-name") || peerLabel;
    if (inboxList) {
      Array.prototype.forEach.call(inboxList.querySelectorAll(".call-inbox__item"), function (el) {
        el.classList.toggle("is-selected", el === btn);
      });
    }
    pickEl.value = id;
    for (var i = 0; i < pickEl.options.length; i++) {
      if (pickEl.options[i].value === id) {
        pickEl.selectedIndex = i;
        break;
      }
    }
    if (peerNameEl) peerNameEl.textContent = name;
    if (peerInitialsEl) peerInitialsEl.textContent = initials(name);
  }

  function bindInboxClicks() {
    if (!inboxList) return;
    Array.prototype.forEach.call(inboxList.querySelectorAll(".call-inbox__item"), function (btn) {
      btn.addEventListener("click", function () {
        selectInboxItem(btn);
      });
    });
  }

  function refreshOpenCalls() {
    if (role !== "mentor" || inCall || !pickEl) return;
    fetch("call.php?ajax=1&action=open_calls", { credentials: "same-origin" })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        if (!data.ok) return;
        var calls = data.calls || [];
        var selected = pickEl.value;
        pickEl.innerHTML = "";
        calls.forEach(function (row) {
          var opt = document.createElement("option");
          opt.value = String(row.id);
          opt.setAttribute("data-language", row.language || "english");
          opt.textContent = row.full_name + " · " + row.language;
          pickEl.appendChild(opt);
        });
        if (selected) {
          for (var i = 0; i < pickEl.options.length; i++) {
            if (pickEl.options[i].value === selected) {
              pickEl.selectedIndex = i;
              break;
            }
          }
        }

        if (inboxList) {
          inboxList.innerHTML = "";
          calls.forEach(function (row, idx) {
            var li = document.createElement("li");
            var btn = document.createElement("button");
            btn.type = "button";
            btn.className = "call-inbox__item";
            btn.setAttribute("data-call-id", String(row.id));
            btn.setAttribute("data-language", row.language || "english");
            btn.setAttribute("data-name", row.full_name || "");
            if (String(row.id) === pickEl.value || (!pickEl.value && idx === 0)) {
              btn.classList.add("is-selected");
            }
            btn.innerHTML =
              '<span class="call-inbox__avatar">' + escapeHtml(initials(row.full_name)) + "</span>" +
              '<span class="call-inbox__meta">' +
              '<span class="call-inbox__name">' + escapeHtml(row.full_name) + "</span>" +
              '<span class="call-inbox__sub">' + escapeHtml((row.language || "").charAt(0).toUpperCase() + (row.language || "").slice(1)) + " · waiting</span>" +
              "</span>";
            li.appendChild(btn);
            inboxList.appendChild(li);
          });
          bindInboxClicks();
          var sel = inboxList.querySelector(".call-inbox__item.is-selected") || inboxList.querySelector(".call-inbox__item");
          if (sel) selectInboxItem(sel);
        }

        if (inboxCount) inboxCount.textContent = String(calls.length);

        var has = calls.length > 0;
        if (joinWrap) joinWrap.hidden = !has;
        if (waitingEl) waitingEl.hidden = has;
        if (inboxList) inboxList.hidden = !has;
        if (!inCall) {
          setBadge(has ? "Incoming" : "Standby", has ? "waiting" : "idle");
          if (has) {
            setStatus("Select a waiting mentee, then join to connect.");
          } else {
            setStatus("No mentees waiting. New calls show up here automatically.");
            if (peerNameEl) peerNameEl.textContent = "Waiting for mentee";
            if (peerInitialsEl) peerInitialsEl.textContent = "–";
          }
        }
      })
      .catch(function () {});
  }

  function beginSession(id, language) {
    callId = id;
    if (language) callLanguage = language;
    lastLine = 0;
    transcriptLines = [];
    captions.innerHTML = "";
    if (captionsEmpty) {
      captionsEmpty.hidden = false;
      captions.appendChild(captionsEmpty);
    } else {
      var empty = document.createElement("p");
      empty.className = "call-captions__empty";
      empty.id = "call-captions-empty";
      empty.textContent = "Captions will appear here once speech is detected.";
      captions.appendChild(empty);
      captionsEmpty = empty;
    }
    inCall = true;
    isOfferer = false;
    if (endBtn) endBtn.hidden = false;
    if (muteBtn) muteBtn.hidden = false;
    if (downloadBtn) {
      downloadBtn.hidden = false;
      downloadBtn.disabled = true;
    }
    if (startBtn) startBtn.disabled = true;
    if (joinBtn) joinBtn.disabled = true;
    if (langEl) langEl.disabled = true;
    if (joinWrap) joinWrap.hidden = true;
    if (waitingEl) waitingEl.hidden = true;
    if (inboxAside) inboxAside.setAttribute("aria-busy", "true");
    if (inboxList) {
      Array.prototype.forEach.call(inboxList.querySelectorAll(".call-inbox__item"), function (el) {
        el.disabled = true;
      });
    }
    if (joinBtn) joinBtn.hidden = true;
    setBadge("Connecting", "connecting");
    setAvatarState(selfAvatar, "waiting");
    setAvatarState(peerAvatar, "waiting");
    polling = window.setInterval(poll, 2500);
    startWebRtc();
  }

  function startWebRtc() {
    setStatus("Requesting microphone…");
    navigator.mediaDevices.getUserMedia({
      audio: {
        echoCancellation: true,
        noiseSuppression: true,
        autoGainControl: true
      }
    }).then(function (stream) {
      localStream = stream;
      setAvatarState(selfAvatar, "live");
      connectSignaling();
    }).catch(function (err) {
      console.error(err);
      setStatus("Microphone permission is required for the call.");
      setBadge("Mic blocked", "error");
      teardownMedia(false);
    });
  }

  function connectSignaling() {
    try {
      ws = new WebSocket(signalingUrl);
    } catch (e) {
      setStatus("Could not open signaling WebSocket. Is the signaling server running?");
      setBadge("Offline", "error");
      return;
    }

    ws.onopen = function () {
      ws.send(JSON.stringify({ type: "join", room: roomCode() }));
      setStatus("Joined room. Waiting for the other person…");
      setBadge("Waiting", "waiting");
    };

    ws.onerror = function () {
      setStatus("Signaling server unreachable at " + signalingUrl + ". Run: cd signaling && node server.js");
      setBadge("Offline", "error");
    };

    ws.onclose = function () {
      if (inCall) {
        setStatus("Signaling connection closed. Audio may drop if the peer leaves.");
      }
    };

    ws.onmessage = function (event) {
      var msg;
      try {
        msg = JSON.parse(event.data);
      } catch (e) {
        return;
      }

      if (msg.type === "joined") {
        setStatus("Waiting for the other person to join…");
        setupPeerConnection(false);
      } else if (msg.type === "room-full") {
        setStatus("This call room is full. End and start a new call.");
        setBadge("Full", "error");
      } else if (msg.type === "peer-joined") {
        setStatus("Other person joined. Connecting audio…");
        setBadge("Connecting", "connecting");
        isOfferer = true;
        if (!pc) setupPeerConnection(true);
        else ensureDataChannel();
        createOffer();
      } else if (msg.type === "offer") {
        if (!pc) setupPeerConnection(false);
        pc.setRemoteDescription(new RTCSessionDescription(msg.sdp)).then(function () {
          return pc.createAnswer();
        }).then(function (answer) {
          return pc.setLocalDescription(answer).then(function () {
            if (ws && ws.readyState === WebSocket.OPEN) {
              ws.send(JSON.stringify({ type: "answer", sdp: answer }));
            }
          });
        }).catch(function (err) {
          console.error(err);
          setStatus("Could not complete call setup.");
          setBadge("Error", "error");
        });
      } else if (msg.type === "answer") {
        if (pc) {
          pc.setRemoteDescription(new RTCSessionDescription(msg.sdp)).catch(function (err) {
            console.error(err);
          });
        }
      } else if (msg.type === "ice-candidate") {
        if (msg.candidate && pc) {
          pc.addIceCandidate(new RTCIceCandidate(msg.candidate)).catch(function () {});
        }
      } else if (msg.type === "peer-left") {
        setStatus("Other person left the call.");
        setAvatarState(peerAvatar, "idle");
        setBadge("Peer left", "waiting");
      }
    };
  }

  function setupPeerConnection(asOfferer) {
    if (pc) return;
    pc = new RTCPeerConnection(STUN);

    localStream.getTracks().forEach(function (track) {
      pc.addTrack(track, localStream);
    });

    pc.ontrack = function (event) {
      if (remoteAudio) {
        remoteAudio.srcObject = event.streams[0] || new MediaStream([event.track]);
        var playPromise = remoteAudio.play();
        if (playPromise && typeof playPromise.catch === "function") {
          playPromise.catch(function () {});
        }
      }
      setStatus("Call connected · live captions via Vosk (in-browser).");
      setBadge("Live", "live");
      setAvatarState(peerAvatar, "live");
      startTimer();
    };

    pc.onicecandidate = function (event) {
      if (event.candidate && ws && ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({ type: "ice-candidate", candidate: event.candidate }));
      }
    };

    pc.onconnectionstatechange = function () {
      if (!pc) return;
      if (pc.connectionState === "connected") {
        setBadge("Live", "live");
        setAvatarState(peerAvatar, "live");
      } else if (pc.connectionState === "failed") {
        setBadge("Failed", "error");
        setStatus("Connection failed. Check network and try again.");
      } else if (pc.connectionState === "disconnected") {
        setBadge("Reconnecting", "connecting");
      }
    };

    if (asOfferer) ensureDataChannel();

    pc.ondatachannel = function (event) {
      setupDataChannel(event.channel);
    };

    startTranscription();
  }

  function ensureDataChannel() {
    if (!pc || dataChannel) return;
    dataChannel = pc.createDataChannel("transcript");
    setupDataChannel(dataChannel);
  }

  function setupDataChannel(channel) {
    channel.onopen = function () {
      dataChannel = channel;
    };
    channel.onmessage = function (event) {
      var data;
      try {
        data = JSON.parse(event.data);
      } catch (e) {
        return;
      }
      if (data && data.text) {
        if (data.who && peerNameEl) peerNameEl.textContent = data.who;
        addCaption(data.who || peerLabel, data.text, false);
      }
    };
    if (channel.label === "transcript") dataChannel = channel;
  }

  function createOffer() {
    if (!pc) setupPeerConnection(true);
    ensureDataChannel();
    pc.createOffer().then(function (offer) {
      return pc.setLocalDescription(offer).then(function () {
        if (ws && ws.readyState === WebSocket.OPEN) {
          ws.send(JSON.stringify({ type: "offer", sdp: offer }));
        }
      });
    }).catch(function (err) {
      console.error(err);
      setStatus("Could not create call offer.");
      setBadge("Error", "error");
    });
  }

  function selectedLang() {
    return callLanguage || (langEl && langEl.value) || "english";
  }

  function startTranscription() {
    if (voskRecognizer || voskStarting) return;
    if (typeof Vosk === "undefined") {
      setStatus("Call audio may work, but the Vosk library failed to load. Check your network.");
      return;
    }
    if (!localStream) return;

    var lang = selectedLang();
    var modelUrl = MODEL_URLS[lang] || MODEL_URLS.english;
    var langLabel = lang === "filipino" ? "Filipino" : "English";
    voskStarting = true;

    var modelPromise = voskModelCache[lang]
      ? Promise.resolve(voskModelCache[lang])
      : (function () {
          setStatus("Downloading " + langLabel + " transcription model (first time only)…");
          return Vosk.createModel(modelUrl).then(function (model) {
            voskModelCache[lang] = model;
            return model;
          });
        })();

    modelPromise.then(function (model) {
      voskStarting = false;
      if (!inCall || !localStream || voskRecognizer) return;
      if (pc && pc.connectionState === "connected") {
        setStatus("Call connected · live captions ready.");
      } else {
        setStatus("Captions ready. Waiting for audio connection…");
      }

      voskAudioContext = new AudioContext();
      voskRecognizer = new model.KaldiRecognizer(voskAudioContext.sampleRate);
      voskRecognizer.setWords(true);

      voskRecognizer.on("result", function (message) {
        var text = (message.result && message.result.text ? message.result.text : "").trim();
        if (!text) return;
        var display = isMuted ? text + " (muted — not sent)" : text;
        addCaption(displayName, display, true);
        if (!isMuted) {
          post("line", { text: text });
          if (dataChannel && dataChannel.readyState === "open") {
            dataChannel.send(JSON.stringify({ who: displayName, text: text }));
          }
        }
      });

      voskSource = voskAudioContext.createMediaStreamSource(localStream);
      voskRecognizerNode = voskAudioContext.createScriptProcessor(4096, 1, 1);
      voskRecognizerNode.onaudioprocess = function (event) {
        try {
          voskRecognizer.acceptWaveform(event.inputBuffer);
        } catch (e) {
          console.warn("[vosk] acceptWaveform failed:", e);
        }
      };
      // Keep the processor graph alive without playing mic into speakers (echo).
      voskGain = voskAudioContext.createGain();
      voskGain.gain.value = 0;
      voskSource.connect(voskRecognizerNode);
      voskRecognizerNode.connect(voskGain);
      voskGain.connect(voskAudioContext.destination);
      if (voskAudioContext.state === "suspended") {
        voskAudioContext.resume().catch(function () {});
      }
    }).catch(function (err) {
      voskStarting = false;
      console.error("[vosk] failed:", err);
      setStatus(
        "Audio may still work. Transcription failed — ensure model files exist in assets/models/ (" +
          langLabel +
          ")."
      );
    });
  }

  function stopTranscription() {
    if (voskSource) {
      try { voskSource.disconnect(); } catch (e) {}
      voskSource = null;
    }
    if (voskRecognizerNode) {
      try { voskRecognizerNode.disconnect(); } catch (e) {}
      voskRecognizerNode = null;
    }
    if (voskGain) {
      try { voskGain.disconnect(); } catch (e) {}
      voskGain = null;
    }
    if (voskAudioContext) {
      try { voskAudioContext.close(); } catch (e) {}
      voskAudioContext = null;
    }
    if (voskRecognizer) {
      try { voskRecognizer.remove(); } catch (e) {}
      voskRecognizer = null;
    }
    voskStarting = false;
  }

  function teardownMedia(resetButtons) {
    inCall = false;
    isOfferer = false;
    stopTranscription();
    stopTimer();
    if (polling) {
      window.clearInterval(polling);
      polling = null;
    }
    if (pc) {
      try { pc.close(); } catch (e) {}
      pc = null;
    }
    if (ws) {
      try { ws.close(); } catch (e) {}
      ws = null;
    }
    if (localStream) {
      localStream.getTracks().forEach(function (t) { t.stop(); });
      localStream = null;
    }
    dataChannel = null;
    isMuted = false;
    if (muteBtn) {
      muteBtn.setAttribute("aria-pressed", "false");
      muteBtn.textContent = "Mute";
    }
    if (remoteAudio) remoteAudio.srcObject = null;
    setAvatarState(selfAvatar, "idle");
    setAvatarState(peerAvatar, "idle");
    if (resetButtons) {
      if (startBtn) startBtn.disabled = false;
      if (joinBtn) {
        joinBtn.disabled = false;
        joinBtn.hidden = false;
      }
      if (langEl) langEl.disabled = false;
      if (inboxAside) inboxAside.removeAttribute("aria-busy");
      if (role === "mentor") refreshOpenCalls();
    }
  }

  function transcriptPlainText() {
    return transcriptLines
      .map(function (l) {
        return "[" + l.time + "] " + l.who + ": " + l.text;
      })
      .join("\n");
  }

  function toggleMute() {
    if (!localStream) return;
    isMuted = !isMuted;
    localStream.getAudioTracks().forEach(function (track) {
      track.enabled = !isMuted;
    });
    muteBtn.setAttribute("aria-pressed", isMuted ? "true" : "false");
    muteBtn.textContent = isMuted ? "Unmute" : "Mute";
    setAvatarState(selfAvatar, isMuted ? "muted" : "live");
  }

  function downloadTranscript() {
    var header =
      "PHLOURISH Call Transcript\nCall ID: " +
      callId +
      "\nDate: " +
      new Date().toLocaleString() +
      "\n\n";
    var body = transcriptPlainText();
    var blob = new Blob([header + body], { type: "text/plain" });
    var url = URL.createObjectURL(blob);
    var a = document.createElement("a");
    a.href = url;
    a.download = "transcript-call-" + (callId || "session") + ".txt";
    a.click();
    URL.revokeObjectURL(url);
  }

  if (startBtn) {
    startBtn.addEventListener("click", function () {
      var lang = (langEl && langEl.value) || "english";
      callLanguage = lang;
      setBadge("Starting", "connecting");
      post("start", { language: lang }).then(function (data) {
        if (!data.ok) {
          setStatus(data.error || "Could not start the call.");
          setBadge("Error", "error");
          return;
        }
        beginSession(data.call_id, data.language || lang);
      }).catch(function () {
        setStatus("Could not start the call.");
        setBadge("Error", "error");
      });
    });
  }

  if (joinBtn && pickEl) {
    joinBtn.addEventListener("click", function () {
      callId = Number(pickEl.value);
      var opt = pickEl.options[pickEl.selectedIndex];
      var langFromOpt = opt ? opt.getAttribute("data-language") : null;
      var selectedItem = inboxList && inboxList.querySelector(".call-inbox__item.is-selected");
      var nameFromItem = selectedItem ? selectedItem.getAttribute("data-name") : null;
      if (peerNameEl) {
        peerNameEl.textContent = nameFromItem || (opt ? opt.textContent.split(" · ")[0] : peerLabel);
      }
      if (peerInitialsEl && peerNameEl) {
        peerInitialsEl.textContent = initials(peerNameEl.textContent);
      }
      setBadge("Joining", "connecting");
      post("join").then(function (data) {
        if (!data.ok) {
          setStatus(data.error || "Could not join.");
          setBadge("Error", "error");
          return;
        }
        beginSession(callId, data.language || langFromOpt || "english");
      }).catch(function () {
        setStatus("Could not join.");
        setBadge("Error", "error");
      });
    });
  }

  if (muteBtn) {
    muteBtn.addEventListener("click", toggleMute);
  }

  if (downloadBtn) {
    downloadBtn.addEventListener("click", downloadTranscript);
  }

  if (endBtn) {
    endBtn.addEventListener("click", function () {
      var plain = transcriptPlainText();
      endBtn.disabled = true;
      post("end", { transcript: plain }).then(function () {
        teardownMedia(true);
        if (downloadBtn) {
          downloadBtn.hidden = false;
          downloadBtn.disabled = transcriptLines.length === 0;
        }
        window.location.href = "account.php?section=transcriptions";
      }).catch(function () {
        endBtn.disabled = false;
        setStatus("Could not end the call. Try again.");
      });
    });
  }

  function probeSignaling() {
    var probe;
    try {
      probe = new WebSocket(signalingUrl);
    } catch (e) {
      if (!inCall) {
        setBadge("Signaling off", "error");
        setStatus("Could not open " + signalingUrl + ". Double-click signaling/start.bat, then refresh.");
      }
      return;
    }
    var done = false;
    var timer = window.setTimeout(function () {
      if (done) return;
      done = true;
      try { probe.close(); } catch (e) {}
      if (!inCall) {
        setBadge("Signaling off", "error");
        setStatus("Signaling timed out at " + signalingUrl + ". Keep signaling/start.bat open, then refresh.");
      }
    }, 3000);
    probe.onopen = function () {
      if (done) return;
      done = true;
      window.clearTimeout(timer);
      try { probe.close(); } catch (e) {}
    };
    probe.onerror = function () {
      if (done) return;
      done = true;
      window.clearTimeout(timer);
      if (!inCall) {
        setBadge("Signaling off", "error");
        setStatus("Signaling is not running at " + signalingUrl + ". Double-click signaling/start.bat, then refresh.");
      }
    };
  }

  probeSignaling();

  if (role === "mentor") {
    bindInboxClicks();
    refreshOpenCalls();
    openCallsPolling = window.setInterval(refreshOpenCalls, 3000);
  }
})();
