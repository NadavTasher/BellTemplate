let database = null;

function bell(loggedIn) {
    if (loggedIn) {
        view("home");
    }
    loadDatabase();
}

function addMedia() {
    let form = fillForm();
    form.append("audio", get("library-add-file").files[0]);
    if (get("library-add-name").value.length > 0) {
        save("media-add", {name: get("library-add-name").value}, (result) => {
            if (result.hasOwnProperty("success") && result.success) {
                loadDatabase(() => {
                    view("library");
                });
            }
        }, form);
    }
}

function addPreset(name, callback = undefined) {
    save("preset-add", {preset: name}, () => {
        loadDatabase(callback);
    });
}

function addTime(hour, minute, callback = undefined) {
    save("time-add", {time: (parseInt(hour) * 60 + parseInt(minute))}, () => {
        loadDatabase(callback);
    });
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
            updateSubmenus();
            if (callback !== undefined) callback();
        });
    });
}

function loadPreset(name) {
    if (database.hasOwnProperty("queue")) {
        clear("preset-queue");
        for (let key in database.queue) {
            if (database.queue.hasOwnProperty(key)) {
                let div = document.createElement("div");
                let time = document.createElement("p");
                let select = document.createElement("select");
                let second = document.createElement("input");
                let none = document.createElement("option");
                let change = () => {
                    if (!isNaN(parseFloat(second.value))) {
                        if (select.value !== "null") {
                            save("queue-add", {
                                time: key,
                                preset: name,
                                media: select.value,
                                second: parseFloat(second.value)
                            });
                        } else {
                            save("queue-remove", {time: key, preset: name});
                        }
                    }
                };
                div.classList.add("sideways");
                time.innerText = ((parseInt(key) - parseInt(key) % 60) / 60) + ":" + ((parseInt(key) % 60 < 10) ? ("0" + parseInt(key) % 60) : (parseInt(key) % 60));
                second.type = "number";
                second.placeholder = "Second";
                second.min = 0;
                none.value = "null";
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
                if (database.queue[key].hasOwnProperty(name) && database.queue[key][name].hasOwnProperty("media") && database.queue[key][name].hasOwnProperty("second")) {
                    select.value = database.queue[key][name].media;
                    second.value = database.queue[key][name].second;
                } else {
                    select.value = "null";
                    second.value = 0;
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

function removePreset(name, callback = undefined) {
    save("preset-remove", {preset: name}, () => {
        loadDatabase(callback);
    });
}

function removeTime(time, callback = undefined) {
    save("time-remove", {time: time}, () => {
        loadDatabase(callback);
    });
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
    if (!isNaN(parseFloat(duration)))
        save("duration-set", {duration: parseFloat(duration)});
}

function setMute(state, save = true) {
    get("mute-state").innerText = "State: " + (state ? "Muted" : "Not Muted");
    if (save)
        save("mute-set", {mute: state});
}

function setPreset(name, callback = undefined) {
    save("preset-set", {preset: name}, () => {
        loadDatabase(callback);
    });
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
        setMute(database.mute, false);
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

function updateSubmenus() {
    // Time remove submenu
    if (database.hasOwnProperty("queue")) {
        clear("time-remove-list");
        for (let key in database.queue) {
            if (database.queue.hasOwnProperty(key)) {
                let div = document.createElement("div");
                let time = document.createElement("p");
                let button = document.createElement("button");
                div.classList.add("sideways");
                time.innerText = ((parseInt(key) - parseInt(key) % 60) / 60) + ":" + ((parseInt(key) % 60 < 10) ? ("0" + parseInt(key) % 60) : (parseInt(key) % 60));
                button.innerText = "Remove";
                button.onclick = () => {
                    removeTime(key);
                };
                div.appendChild(time);
                div.appendChild(button);
                get("time-remove-list").appendChild(div);
            }
        }
    }
    // Preset remove submenu
    if (database.hasOwnProperty("presets")) {
        clear("preset-remove-list");
        for (let i = 0; i < database.presets.length; i++) {
            let value = database.presets[i];
            let div = document.createElement("div");
            let name = document.createElement("p");
            let button = document.createElement("button");
            div.classList.add("sideways");
            name.innerText = value;
            button.innerText = "Remove";
            button.onclick = () => {
                removePreset(value);
            };
            div.appendChild(name);
            div.appendChild(button);
            get("preset-remove-list").appendChild(div);
        }
    }
}