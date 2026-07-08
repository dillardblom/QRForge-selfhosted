// Preset system + random style button for the qr code generation forms (Fase 3, priority 2).
(function () {
    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function setColor(id, hex) {
        var input = document.getElementById(id);
        if (!input) {
            return;
        }
        input.value = hex;
        input.dispatchEvent(new Event('change'));
        try {
            // Sync the bootstrap-colorpicker widget/swatch if it was initialized on this input.
            if (window.jQuery) {
                jQuery(input).colorpicker('setValue', hex);
            }
        } catch (e) {
            // Colorpicker not initialized on this page - the raw value above is still correct.
        }
    }

    function randomHexColor() {
        var value = Math.floor(Math.random() * 0xFFFFFF).toString(16);
        return '#' + ('000000' + value).slice(-6);
    }

    function loadPresets() {
        var select = document.getElementById('preset_select');
        if (!select) {
            return;
        }

        fetch('presets.php?action=list')
            .then(function (response) { return response.json(); })
            .then(function (json) {
                if (json.status !== 200) {
                    return;
                }
                json.data.forEach(function (preset) {
                    var option = document.createElement('option');
                    option.value = preset.id;
                    option.textContent = preset.name;
                    option.dataset.foreground = preset.foreground;
                    option.dataset.background = preset.background;
                    option.dataset.level = preset.level;
                    option.dataset.size = preset.size;
                    select.appendChild(option);
                });
            })
            .catch(function () { /* presets are a nice-to-have, fail silently */ });
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadPresets();

        var randomBtn = document.getElementById('random_style_btn');
        if (randomBtn) {
            randomBtn.addEventListener('click', function () {
                setColor('foreground', randomHexColor());
                setColor('background', randomHexColor());
            });
        }

        var presetSelect = document.getElementById('preset_select');
        if (presetSelect) {
            presetSelect.addEventListener('change', function () {
                var option = presetSelect.options[presetSelect.selectedIndex];
                if (!option.value) {
                    return;
                }

                setColor('foreground', option.dataset.foreground);
                setColor('background', option.dataset.background);

                var levelSelect = document.querySelector('select[name="level"]');
                if (levelSelect) {
                    levelSelect.value = option.dataset.level;
                }

                var sizeSelect = document.getElementById('size');
                if (sizeSelect) {
                    sizeSelect.value = option.dataset.size;
                }
            });
        }

        var saveBtn = document.getElementById('preset_save_btn');
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                var nameInput = document.getElementById('preset_name');
                var name = nameInput.value.trim();

                if (!name) {
                    alert('Enter a name for this preset first.');
                    return;
                }

                var body = new URLSearchParams();
                body.set('action', 'save');
                body.set('name', name);
                body.set('foreground', document.getElementById('foreground').value);
                body.set('background', document.getElementById('background').value);
                body.set('level', document.querySelector('select[name="level"]').value);
                body.set('size', document.getElementById('size').value);

                fetch('presets.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken() },
                    body: body
                })
                    .then(function (response) { return response.json(); })
                    .then(function (json) {
                        if (json.status !== 200) {
                            alert('Could not save preset: ' + json.data);
                            return;
                        }

                        var option = document.createElement('option');
                        option.value = json.data.id;
                        option.textContent = json.data.name;
                        option.dataset.foreground = document.getElementById('foreground').value;
                        option.dataset.background = document.getElementById('background').value;
                        option.dataset.level = document.querySelector('select[name="level"]').value;
                        option.dataset.size = document.getElementById('size').value;
                        presetSelect.appendChild(option);
                        presetSelect.value = option.value;
                        nameInput.value = '';
                    })
                    .catch(function () { alert('Could not save preset (network error).'); });
            });
        }

        var deleteBtn = document.getElementById('preset_delete_btn');
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                var option = presetSelect.options[presetSelect.selectedIndex];
                if (!option.value) {
                    return;
                }

                if (!confirm('Delete preset "' + option.textContent + '"?')) {
                    return;
                }

                var body = new URLSearchParams();
                body.set('action', 'delete');
                body.set('id', option.value);

                fetch('presets.php', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': csrfToken() },
                    body: body
                })
                    .then(function (response) { return response.json(); })
                    .then(function (json) {
                        if (json.status === 200) {
                            option.remove();
                        } else {
                            alert('Could not delete preset: ' + json.data);
                        }
                    })
                    .catch(function () { alert('Could not delete preset (network error).'); });
            });
        }
    });
})();
