function bell(loggedIn) {
    if (loggedIn) {
        view("home");
    }
    loadDatabase();
}

function loadDatabase() {
    fetch("files/bell/database.json", {
        method: "get"
    }).then(response => {
        response.text().then((result) => {
            let json = JSON.parse(result);
            if (json.hasOwnProperty("mute")) {
                get("mute-state").innerText = muteTextForState(json.mute);
            }
            if (json.hasOwnProperty("duration")) {
                get("duration").value = json.duration;
            }
            if (json.hasOwnProperty("presets")) {
                for (let i = 0; i < json.presets.length; i++) {
                    let preset = document.createElement("option");
                    preset.value = json.presets[i];
                    preset.innerText = json.presets[i];
                    get("preset-selector").appendChild(preset);

                    let queue = document.createElement("div");
                    queue.id = "preset-queue-" + json.presets[i];

                }
                get("preset-selector").value = json.hasOwnProperty("preset") ? json.preset : "";
            }
        });
    });
}

function setMute(state) {
    get("mute-state").innerText = muteTextForState(state);
    saveMute(state);
}

function muteTextForState(state) {
    return "State: " + (state ? "Muted" : "Not Muted");
}

function prompt(title, description, callback) {

}