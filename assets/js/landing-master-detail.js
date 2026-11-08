(function () {
  var characters = [
    {
      name: "Alex",
      bio: "Protagonist. University student masking depression and engaging in private self-harm behaviors.",
      color: "#7fd3d2",
      image: "assets/img/portraits/Alex1.png"
    },
    {
      name: "Cass",
      bio: "Alex's roommate. Nursing student working night shifts.",
      color: "#5bb8b7",
      image: "assets/img/portraits/Cass1.png"
    },
    {
      name: "Jenna",
      bio: "Longtime close friend of Alex's and the story's primary source of harm.",
      color: "#3aa3a2",
      image: "assets/img/portraits/Jenna1.png"
    },
    {
      name: "Leo",
      bio: "Quiet classmate and Alex's most consistent source of unforced kindness.",
      color: "#2c8f8e",
      image: "assets/img/portraits/Leo1.png"
    },
    {
      name: "Maya",
      bio: "Passive bystander and casual acquaintance of Alex's.",
      color: "#1f7c7b",
      image: "assets/img/portraits/Maya1.png"
    },
    {
      name: "Sari-Sari Store Owner",
      bio: "An emotionally safe presence with no stake in Alex's social world.",
      color: "#146968",
      image: "assets/img/portraits/Vendor1.png"
    },
    {
      name: "Professor Reyes",
      bio: "Minor authority figure representing institutional indifference.",
      color: "#0b5554",
      image: "assets/img/portraits/Professor1.png"
    }
  ];

  var listItems = document.querySelectorAll(".left-character-list li");
  var imageEl = document.querySelector(".character-image");
  var nameEl = document.querySelector(".character-name");
  var bioEl = document.querySelector(".character-bio");

  if (!listItems.length || !imageEl || !nameEl || !bioEl) {
    return;
  }

  function portraitStyle(character) {
    if (character.image) {
      return "center top / cover no-repeat url('" + character.image + "'), " + character.color;
    }
    return character.color;
  }

  function showCharacter(index) {
    var character = characters[index];
    if (!character) {
      return;
    }

    listItems.forEach(function (item) {
      item.classList.toggle("is-selected", Number(item.getAttribute("data-index")) === index);
    });

    imageEl.style.background = portraitStyle(character);
    nameEl.textContent = character.name;
    bioEl.textContent = character.bio;

    [imageEl, nameEl, bioEl].forEach(function (el) {
      el.classList.remove("character-panel-enter");
      void el.offsetWidth;
      el.classList.add("character-panel-enter");
    });
  }

  listItems.forEach(function (item) {
    var index = Number(item.getAttribute("data-index"));
    if (characters[index]) {
      item.style.background = portraitStyle(characters[index]);
      item.setAttribute("aria-label", characters[index].name);
      item.setAttribute("title", characters[index].name);
    }
    item.addEventListener("click", function () {
      showCharacter(index);
    });
  });

  showCharacter(0);
})();
