// Preset system + random style button for the qr code generation forms (Fase 3, priority 2).
//
// The static qr "add" page stacks all qr-type forms (text/email/.../wifi/bitcoin/...) into
// the DOM at once as Bootstrap tab-panes, and every one of them repeats the same element ids
// (foreground, background, size, random_style_btn, ...). getElementById/getElementsBy* only
// ever finds the *first* of those (the "Text" tab), so every helper below is scoped to the
// specific tab-pane/form the triggering element lives in, and wired up via querySelectorAll
// so every tab gets working listeners, not just the first one.
(function () {
    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function scopeOf(el) {
        return el.closest('.tab-pane') || el.closest('form') || document;
    }

    function setColor(scope, id, hex) {
        var input = scope.querySelector('#' + id);
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

    function updateStylePreview(scope) {
        var swatch = scope.querySelector('#style_preview_swatch');
        var text = scope.querySelector('#style_preview_text');
        if (!swatch || !text) {
            return;
        }

        var foreground = scope.querySelector('#foreground');
        var background = scope.querySelector('#background');
        var levelSelect = scope.querySelector('select[name="level"]');
        var sizeSelect = scope.querySelector('#size');

        var fg = foreground ? foreground.value : '#000000';
        var bg = background ? background.value : '#ffffff';

        swatch.style.background = bg;
        swatch.style.borderColor = fg;

        var parts = [];
        if (levelSelect) {
            parts.push('Precision: ' + levelSelect.value);
        }
        if (sizeSelect) {
            parts.push('Size: ' + sizeSelect.value + 'px');
        }
        text.textContent = parts.join(' · ');
    }

    function loadPresetsInto(select) {
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
        document.querySelectorAll('#preset_select').forEach(loadPresetsInto);

        document.querySelectorAll('#style_preview').forEach(function (row) {
            updateStylePreview(scopeOf(row));
        });

        document.querySelectorAll('#foreground, #background').forEach(function (input) {
            input.addEventListener('change', function () {
                updateStylePreview(scopeOf(input));
            });
        });

        document.querySelectorAll('select[name="level"]').forEach(function (select) {
            select.addEventListener('change', function () {
                updateStylePreview(scopeOf(select));
            });
        });

        document.querySelectorAll('#size').forEach(function (select) {
            select.addEventListener('change', function () {
                updateStylePreview(scopeOf(select));
            });
        });

        document.querySelectorAll('#random_style_btn').forEach(function (randomBtn) {
            randomBtn.addEventListener('click', function () {
                var scope = scopeOf(randomBtn);
                setColor(scope, 'foreground', randomHexColor());
                setColor(scope, 'background', randomHexColor());
                updateStylePreview(scope);
            });
        });

        document.querySelectorAll('#preset_select').forEach(function (presetSelect) {
            presetSelect.addEventListener('change', function () {
                var option = presetSelect.options[presetSelect.selectedIndex];
                if (!option.value) {
                    return;
                }

                var scope = scopeOf(presetSelect);

                setColor(scope, 'foreground', option.dataset.foreground);
                setColor(scope, 'background', option.dataset.background);

                var levelSelect = scope.querySelector('select[name="level"]');
                if (levelSelect) {
                    levelSelect.value = option.dataset.level;
                }

                var sizeSelect = scope.querySelector('#size');
                if (sizeSelect) {
                    sizeSelect.value = option.dataset.size;
                }

                updateStylePreview(scope);
            });
        });

        document.querySelectorAll('#preset_save_btn').forEach(function (saveBtn) {
            saveBtn.addEventListener('click', function () {
                var scope = scopeOf(saveBtn);
                var nameInput = scope.querySelector('#preset_name');
                var name = nameInput.value.trim();

                if (!name) {
                    alert('Enter a name for this preset first.');
                    return;
                }

                var foreground = scope.querySelector('#foreground').value;
                var background = scope.querySelector('#background').value;
                var level = scope.querySelector('select[name="level"]').value;
                var size = scope.querySelector('#size').value;

                var body = new URLSearchParams();
                body.set('action', 'save');
                body.set('name', name);
                body.set('foreground', foreground);
                body.set('background', background);
                body.set('level', level);
                body.set('size', size);

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

                        // The preset list is shared across every tab, so mirror the new
                        // option into every preset_select on the page, not just this one.
                        document.querySelectorAll('#preset_select').forEach(function (select) {
                            var option = document.createElement('option');
                            option.value = json.data.id;
                            option.textContent = json.data.name;
                            option.dataset.foreground = foreground;
                            option.dataset.background = background;
                            option.dataset.level = level;
                            option.dataset.size = size;
                            select.appendChild(option);

                            if (select === scope.querySelector('#preset_select')) {
                                select.value = option.value;
                            }
                        });

                        nameInput.value = '';
                    })
                    .catch(function () { alert('Could not save preset (network error).'); });
            });
        });

        document.querySelectorAll('#preset_delete_btn').forEach(function (deleteBtn) {
            deleteBtn.addEventListener('click', function () {
                var scope = scopeOf(deleteBtn);
                var presetSelect = scope.querySelector('#preset_select');
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
                            // Remove the matching option from every preset_select on the page.
                            document.querySelectorAll('#preset_select').forEach(function (select) {
                                var match = select.querySelector('option[value="' + option.value + '"]');
                                if (match) {
                                    match.remove();
                                }
                            });
                        } else {
                            alert('Could not delete preset: ' + json.data);
                        }
                    })
                    .catch(function () { alert('Could not delete preset (network error).'); });
            });
        });
    });
})();
