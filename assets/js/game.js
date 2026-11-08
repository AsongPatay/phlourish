(function () {
  var cfg = window.PHLOURISH_GAME || {};
  var valueEl = document.getElementById("game-mhm-value");
  var barEl = document.getElementById("game-mhm-bar");
  var questEl = document.getElementById("game-quest-label");
  var postEl = document.getElementById("game-posttest");
  var frame = document.getElementById("game-frame");

  function applyProgress(data) {
    if (!data) return;
    if (typeof data.mhm === "number" && valueEl && barEl) {
      var mhm = Math.max(0, Math.min(100, data.mhm));
      valueEl.textContent = String(mhm);
      barEl.style.width = mhm + "%";
    }
    if (questEl && data.quest_id) {
      questEl.textContent = data.quest_id;
    }
    if (data.completed && postEl && !cfg.hasPost) {
      postEl.hidden = false;
    }
  }

  function saveProgress(data) {
    fetch("api/game-progress.php", {
      method: "POST",
      credentials: "same-origin",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        csrf_token: cfg.csrf || "",
        mhm: data.mhm,
        quest_id: data.quest_id || "",
        completed: data.completed ? 1 : 0
      })
    })
      .then(function (res) {
        return res.json();
      })
      .then(applyProgress)
      .catch(function () {});
  }

  window.addEventListener("message", function (event) {
    if (event.origin !== window.location.origin) return;
    var data = event.data;
    if (!data || data.type !== "phlourish_progress") return;
    applyProgress(data);
    saveProgress(data);
  });

  if (frame) {
    frame.addEventListener("load", function () {
      try {
        frame.contentWindow.focus();
      } catch (err) {}
    });
  }
})();
