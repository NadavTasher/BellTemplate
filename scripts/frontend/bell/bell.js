let database = null;

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
            database = JSON.parse(result);
            updateGeneral();
            updatePresets();
            updateTimes();
            updateLibrary();
        });
    });
}

function updateGeneral() {
    updateMute();
    updateDuration();
}

function updatePresets() {

}

function updateTimes() {

}

function updateLibrary() {
    if (database.hasOwnProperty("library")) {
        for (let key in database.library) {
            if (database.library.hasOwnProperty(key)) {
                let value = database.library[key];

            }
        }
    }
}

function updateMute() {
    if (database.hasOwnProperty("mute")) {
        get("mute-state").innerText = muteTextForState(database.mute);
    }
}

function updateDuration() {
    if (database.hasOwnProperty("duration")) {
        get("duration").value = database.duration;
    }
}

function update() {


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