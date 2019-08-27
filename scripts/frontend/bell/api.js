/**
 * Copyright (c) 2019 Nadav Tasher
 * https://github.com/NadavTasher/BellTemplate/
 **/

const BELL_API = "bell";
const BELL_ENDPOINT = document.getElementsByName("endpoint")[0].getAttribute("content");

let database = null;

function bell(loggedIn) {
    if (loggedIn) {
        view("app");
        view("home");
    }
    loadDatabase();
}

function addMedia(callback = null) {
    let form = accounts_fill();
    form.append("audio", get("library-add-file").files[0]);
    if (get("library-add-name").value.length > 0) {
        command("media-add", {name: get("library-add-name").value}, () => {
            loadDatabase(callback);
        }, form);
    }
}

function addPreset(name, callback = null) {
    command("preset-add", {preset: name}, () => {
        loadDatabase(callback);
    });
}

function addTime(hour, minute, callback = null) {
    command("time-add", {time: (parseInt(hour) * 60 + parseInt(minute))}, () => {
        loadDatabase(callback);
    });
}

function loadDatabase(callback = null) {
    fetch("files/bell/database.json", {
        method: "get",
        cache: "no-store"
    }).then(response => {
        response.text().then((result) => {
            database = JSON.parse(result);
            updateGeneral();
            updatePresets();
            updateLibrary();
            updateSubmenus();
            if (callback !== null) callback();
        });
    });
}

function loadPreset(name) {
    if (database.hasOwnProperty("queue")) {
        clear("preset-queue");
        for (let key in database.queue) {
            if (database.queue.hasOwnProperty(key)) {
                let div = make("div");
                let time = make("p");
                let select = make("select");
                let second = make("input");
                let none = make("option");
                let change = () => {
                    if (!isNaN(parseFloat(second.value))) {
                        if (select.value !== "null") {
                            command("queue-add", {
                                time: key,
                                preset: name,
                                media: select.value,
                                second: parseFloat(second.value)
                            });
                        } else {
                            command("queue-remove", {time: key, preset: name});
                        }
                    }
                };
                row(div);
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
                                let media = make("option");
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

function removePreset(name, callback = null) {
    command("preset-remove", {preset: name}, () => {
        loadDatabase(callback);
    });
}

function removeTime(time, callback = null) {
    command("time-remove", {time: time}, () => {
        loadDatabase(callback);
    });
}

function command(command, parameters, callback = null, form = accounts_fill()) {
    api(BELL_ENDPOINT, BELL_API, command, parameters, callback, form);
}

function setDuration(duration) {
    if (!isNaN(parseFloat(duration)))
        command("duration-set", {duration: parseFloat(duration)});
}

function setMute(state, save = true) {
    get("mute-state").innerText = "State: " + (state ? "Muted" : "Not Muted");
    if (save)
        command("mute-set", {mute: state});
}

function setPreset(name, callback = null) {
    command("preset-set", {preset: name}, () => {
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
                if (value.hasOwnProperty("name") && value.hasOwnProperty("media")) {
                    let div = make("div");
                    let button = make("button");
                    let media = make("p");
                    row(div);
                    media.innerText = value.name;
                    button.innerText = "Listen";
                    button.onclick = () => {
                        window.location = "files/media/" + value.media;
                    };
                    div.appendChild(media);
                    div.appendChild(button);
                    get("library-list").appendChild(div);
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
            let option = make("option");
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
                let div = make("div");
                let time = make("p");
                let button = make("button");
                row(div);
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
            let div = make("div");
            let name = make("p");
            let button = make("button");
            row(div);
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