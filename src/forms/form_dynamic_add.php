<fieldset>
    <div class="col-sm-12 mb-2">
        <div class="row">
            <div class="col-6 col-md-3">
                <label for="foreground">Foreground *</label>
                <div class="input-group my-colorpicker2">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-qrcode"></i></span>
                    </div>
                    
                    <input type="text" class="form-control" id="foreground" name="foreground" value="#000000" required="required">
                </div>
            </div>
                  
            <div class="col-6 col-md-3">
                <label for="background">Background *</label>
                <div class="input-group my-colorpicker2">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-qrcode"></i></span>
                    </div>
                    
                    <input type="text" class="form-control" id="background" name="background" value="#ffffff" required="required">
                </div>
            </div>
                  
            <div class="col-6 col-md-3">
                <label for="level">Precision</label>
                <select name="level" class="form-control">
                    <option value="L">L - Smallest</option>
                    <option value="M">M - Medium</option>
                    <option value="Q">Q - High</option>
                    <option value="H">H - Best</option>
                </select>
            </div>
        
            <div class="col-6 col-md-3">
                <label for="size">Size (px)</label>
                <select name="size" id="size" class="form-control">
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="300">300</option>
                    <option value="400">400</option>
                    <option value="500">500</option>
                    <option value="600">600</option>
                    <option value="700">700</option>
                    <option value="800">800</option>
                    <option value="900">900</option>
                    <option value="1000">1000</option>
                </select>
            </div>
        </div>
    </div>

    <div class="col-sm-12 mb-2">
        <div class="row">
            <div class="col-6 col-md-3">
                <label>&nbsp;</label>
                <button type="button" id="random_style_btn" class="btn btn-outline-secondary btn-block">
                    <i class="fa fa-dice"></i> Random style
                </button>
            </div>

            <div class="col-6 col-md-3">
                <label for="preset_select">Load preset</label>
                <div class="input-group">
                    <select id="preset_select" class="form-control">
                        <option value="">-- Select --</option>
                    </select>
                    <div class="input-group-append">
                        <button type="button" id="preset_delete_btn" class="btn btn-outline-secondary" title="Delete selected preset"><i class="fa fa-trash"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <label for="preset_name">Save as preset</label>
                <div class="input-group">
                    <input type="text" id="preset_name" class="form-control" placeholder="Preset name" maxlength="50">
                    <div class="input-group-append">
                        <button type="button" id="preset_save_btn" class="btn btn-outline-secondary"><i class="fa fa-save"></i></button>
                    </div>
                </div>
            </div>

            <div class="col-6 col-md-3">
                <label>Style preview</label>
                <div id="style_preview" class="d-flex align-items-center">
                    <span id="style_preview_swatch" style="display:inline-block;width:38px;height:38px;border:3px solid #000;background:#fff;border-radius:4px;"></span>
                    <small id="style_preview_text" class="ml-2 text-muted"></small>
                </div>
            </div>
        </div>
    </div>

    <script src="dist/js/qrcode-style-tools.js?nocache=<?php print rand();?>"></script>

<!-- Its use is not recommended. Read the documentation
    <div class="form-group">
        <label for="logo">Logo</label>
        <?php //include 'logo.php' ?>
    </div>
    -->

    <div class="col-sm-4">
        <div class="form-group">
            <label for="link">URL *</label>
            <input type="url" pattern="http.*://.*" name="link" value="" placeholder="https://example.com" class="form-control" required="required" id="link">
        </div>
    </div>
    
    
    <div class="col-sm-4">
        <div class="form-group">
            <label for="identifier">Redirect identifier</label>
            <p>It will be automatically generated</p>
        </div>
    </div>
    
<div class="col-sm-12 mb-2">
  <div class="row">
    <div class="col-sm-4">
        <div class="form-group">
            <label for="frame_text">Frame text</label>
            <input type="text" name="frame_text" value="" placeholder="e.g. Scan me" maxlength="60" class="form-control" id="frame_text">
            <small class="form-text text-muted">Optional label rendered below the code. Only applies to PNG/JPEG/GIF, not SVG/EPS.</small>
        </div>
    </div>

    <div class="col-6 col-md-2">
        <div class="form-group">
            <label for="frame_font">Frame font</label>
            <select name="frame_font" id="frame_font" class="form-control">
                <option value="sans" selected>Sans</option>
                <option value="sans-bold">Sans Bold</option>
                <option value="serif">Serif</option>
                <option value="serif-bold">Serif Bold</option>
                <option value="mono">Monospace</option>
                <option value="mono-bold">Monospace Bold</option>
            </select>
        </div>
    </div>

    <div class="col-6 col-md-2">
        <div class="form-group">
            <label for="frame_font_size">Frame font size</label>
            <input type="number" name="frame_font_size" id="frame_font_size" value="16" min="8" max="60" class="form-control">
        </div>
    </div>

    <div class="col-sm-4">
        <div class="form-group">
            <label for="icon">Icon above QR code</label>
            <input type="file" name="icon" id="icon" accept="image/png,image/jpeg,image/gif" class="form-control-file">
            <small class="form-text text-muted">Optional. PNG/JPEG/GIF, max 1MB. Shown above the code (not embedded in it). Only applies to PNG/JPEG/GIF output.</small>
        </div>
    </div>
  </div>
</div>

    <div class="col-sm-12 mb-2">
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="filename">Filename *</label>
                    <input type="text" name="filename" value="" placeholder="My first Qrcode" class="form-control error" required="required" id="filename">
                </div>
            </div>

            <div class="col-6 col-md-1">
                    <label for="format">Format *</label>
                    <select name="format" class="form-control" required="required">
                        <option value="png" selected>PNG</option>
                        <option value="gif">GIF</option>
                        <option value="jpeg">JPEG</option>
                        <option value="jpg">JPG</option>
                        <option value="svg">SVG</option>
                        <option value="eps">EPS</option>
                    </select>
            </div>

            <?php if ($_SESSION['type'] === 'super') { ?>
            <div class="col-sm-4">
                <div class="form-group">
                    <label for="id_owner">Owner *</label>
                    <select name="id_owner" id="id_owner" class="form-control">
                        <option value="">All (shared with every admin)</option>
                        <?php

                        require_once BASE_PATH . '/lib/Users/Users.php';
                        $users_instance = new Users();
                        $users = $users_instance->getAllUsers();

                        foreach ($users as $user) {
                            $is_self = (int) $user["id"] === (int) $_SESSION["user_id"];
                        ?>
                        <option value="<?php echo $user["id"];?>" <?php echo $is_self ? 'selected' : ''; ?>><?php echo $user["username"];?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <?php } else { ?>
            <input type="hidden" name="id_owner" value="<?php echo $_SESSION["user_id"];?>"/>
            <?php } ?>
        </div>
    </div>
</fieldset>
