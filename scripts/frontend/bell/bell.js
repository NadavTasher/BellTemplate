let database = null;

function bell(loggedIn) {
    if (loggedIn) {
        view("home");
    }
    loadDatabase();
}

function loadDatabase(callback = undefined) {
    fetch("files/bell/database.json", {
        method: "get"
    }).then(response => {
        response.text().then((result) => {
            database = JSON.parse(result);
            updateGeneral();
            updatePresets();
            updateLibrary();
            if (callback !== undefined) callback();
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
                let time = document.createElement("p");
                let select = document.createElement("select");
                let second = document.createElement("input");
                let minute = parseInt(key);
                let change = () => {
                    if (!isNaN(second.value)) {
                        if (select.value !== null) {
                            save("set", {time: key, preset: name, media: select.value, second: second.value});
                        } else {
                            save("remove", {time: key, preset: name});
                        }
                    }
                };
                div.classList.add("sideways");
                time.innerText = ((minute - minute % 60) / 60) + ":" + ((minute % 60 < 10) ? ("0" + minute % 60) : (minute % 60));
                second.type = "number";
                second.placeholder = "Second";
                let none = document.createElement("option");
                none.value = null;
                none.innerText = "None";
                select.appendChild(none);
                if (database.hasOwnProperty("library")) {
                    for (let key in database.library) {
                        if (database.library.hasOwnProperty(key)) {
                            let value = database.library[key];
                            if (value.hasOwnProperty("name")) {
                                let media = document.createElement("option");
                                media.innerText = value.name;
                                media.value = key;
                                select.appendChild(media);
                            }
                        }
                    }
                }
                let value = database.queue[key];
                if (value.hasOwnProperty(name)) {
                    select.value = value[name];
                } else {
                    select.value = null;
                }
                select.oninput = change;
                second.oninput = change;
                div.appendChild(time);
                div.appendChild(select);
                div.appendChild(second);
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
    save("mute", {mute: state});
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
                if (value.hasOwnProperty("name")) {
                    let media = document.createElement("p");
                    media.innerText = value.name;
                    get("library-list").appendChild(media);
                }
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
        if (database.hasOwnProperty("preset"))
            get("preset-list").value = database.preset;
        get("preset-list").oninput();
    }
}

function addTime() {
    let div = document.createElement("div");
    let title = document.createElement("p");
    let time = document.createElement("div");
    let buttons = document.createElement("div");
    let ok = document.createElement("button");
    let cancel = document.createElement("button");
    let hour = document.createElement("input");
    let minute = document.createElement("input");
    let colon = document.createElement("p");
    buttons.classList.add("sideways");
    time.classList.add("sideways");
    time.appendChild(hour);
    time.appendChild(colon);
    time.appendChild(minute);
    buttons.appendChild(ok);
    buttons.appendChild(cancel);
    title.classList.add("title");
    title.innerText = "Add time";
    colon.innerText = ":";
    colon.style.width = "min-content";
    hour.type = "number";
    hour.min = 0;
    hour.max = 23;
    hour.placeholder = "Hour";
    minute.type = "number";
    minute.min = 0;
    minute.max = 59;
    minute.placeholder = "Minute";
    ok.innerText = "Add";
    cancel.innerText = "Cancel";
    ok.onclick = () => {
        save("addTime", {time: (parseInt(hour.value) * 60 + parseInt(minute.value))}, () => {
            loadDatabase(() => {
                view("general");
            });
        });
    };
    cancel.onclick = () => {
        view("general");
    };
    div.appendChild(title);
    div.appendChild(time);
    div.appendChild(buttons);
    prompt(div);
}

function prompt(content) {
    clear("prompt");
    get("prompt").appendChild(content);
    view("prompt");
}

function uploadMedia() {
    let form = fillForm();
    form.append("audio", get("library-add-file").files[0]);
    if (get("library-add-name").value.length > 0) {
        save("upload", {name: get("library-add-name").value}, (result) => {
            if (result.hasOwnProperty("success") && result.success) {
                loadDatabase(() => {
                    view("library");
                });
            }
        }, form);
    }
}
