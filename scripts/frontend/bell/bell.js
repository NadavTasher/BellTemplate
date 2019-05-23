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

function loadPreset(name) {
    console.log(name);
    if (database.hasOwnProperty("queue")) {
        clear("preset-queue");
        for (let key in database.queue) {
            if (database.queue.hasOwnProperty(key)) {
                let div = document.createElement("div");
                div.classList.add("sideways");
                let time = document.createElement("p");
                let minute = parseInt(key);
                time.innerText = (minute - minute % 60) / 60 + ":" + (minute % 60 < 10) ? ("0" + minute % 60) : (minute % 60);
                let value = database.queue[key];
                div.appendChild(time);
                get("preset-queue").appendChild(div);
            }
        }
    }
}

function muteTextForState(state) {
    return "State: " + (state ? "Muted" : "Not Muted");
}

function reload() {
    window.location.reload(true);
}
function save(command, parameters, callback = undefined, form = fillForm()) {
    form.append("bell", JSON.stringify({
        action: command,
        parameters: parameters
    }));
    fetch("scripts/backend/bell/bell.php", {
        method: "post",
        body: form
    }).then(response => {
        response.text().then((result) => {
            let json = JSON.parse(result);
            if (json.hasOwnProperty("bell")) {
                if (json.bell.hasOwnProperty(command)) {
                    if (callback !== undefined)
                        callback(json.bell[command]);
                }
            }
        });
    });
}

function setDuration(duration) {
    if (!isNaN(duration))
        save("duration", {duration: parseFloat(duration)});
}

function setMute(state) {
    get("mute-state").innerText = muteTextForState(state);
    save("mute", {mute: state}, () => {
    });
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

function updateDuration() {
    if (database.hasOwnProperty("duration")) {
        get("duration").value = database.duration;
    }
}

function updateGeneral() {
    updateMute();
    updateDuration();
}

function updateLibrary() {
    if (database.hasOwnProperty("library")) {
        clear("library-list");
        for (let key in database.library) {
            if (database.library.hasOwnProperty(key)) {
                let value = database.library[key];
                let media = document.createElement("p");
                media.innerText = value.name;
                get("library-list").appendChild(media);
            }
        }
    }
}

function updateMute() {
    if (database.hasOwnProperty("mute")) {
        get("mute-state").innerText = muteTextForState(database.mute);
    }
}

function updatePresets() {
    if (database.hasOwnProperty("presets")) {
        clear("preset-list");
        for (let i = 0; i < database.presets.length; i++) {
            let value = database.presets[i];
            let option = document.createElement("option");
            option.innerText = value;
            option.value = value;
            get("preset-list").appendChild(option);
        }
        get("preset-list").oninput();
    }
}

function updateTimes() {

}

function uploadMedia() {
    let form = fillForm();
    form.append("audio", get("library-add-file").files[0]);
    save("upload", {name: get("library-add-name").value}, (result) => {
        if (result.hasOwnProperty("success") && result.success) reload();
    }, form);
}
