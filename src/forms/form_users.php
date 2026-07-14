<fieldset>
    <div class="col-sm-4">
        <div class="form-group">
            <label for="username">Username *</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-user"></i></span>
                </div>

                <input type="text" name="username" placeholder="Username" class="form-control" required="required" value="<?php echo ($edit) ? $user['username'] : ''; ?>" autocomplete="off">
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="form-group">
            <label for="email">Email</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                </div>

                <input type="email" name="email" placeholder="Email" class="form-control" value="<?php echo ($edit) ? htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8') : ''; ?>" autocomplete="off">
            </div>
            <small class="form-text text-muted">Used to log in once set. Leave blank to prompt for it on next login.</small>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="form-group">
            <label for="password">Password *</label>
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fa fa-lock"></i></span>
                </div>

                <input type="password" name="password" placeholder="<?php echo ($edit) ? 'Leave blank to keep current password' : 'Password'; ?>" class="form-control" <?php echo ($edit) ? '' : 'required="required"'; ?> minlength="10" autocomplete="off">
            </div>
        </div>
    </div>

    <?php if ($_SESSION['type'] === 'super'): ?>
    <?php $editing_self = $edit && (int) $user['id'] === (int) $_SESSION['user_id']; ?>
    <div class="col-sm-4">
        <label for="user-type">User type *</label>

        <div class="form-group">
            <div class="radio">
                <label class="radio">
                <input type="radio" name="type" value="super" required="required" <?php echo ($edit && $user['type'] =='super') ? "checked": "" ; ?> <?php echo $editing_self ? "disabled" : ""; ?>/> Super admin</label>
            </div>

            <div class="radio">
                <label class="radio">
                <input type="radio" name="type" value="admin" required="required" <?php echo ($edit && $user['type'] =='admin') ? "checked": "" ; ?> <?php echo $editing_self ? "disabled" : ""; ?>/> Admin</label>
            </div>

            <div class="radio">
                <label class="radio">
                <input type="radio" name="type" value="user" required="required" id="type-user" <?php echo ($edit && $user['type'] =='user') ? "checked": "" ; ?> <?php echo $editing_self ? "disabled" : ""; ?>/> User (read-only)</label>
            </div>
        </div>
        <?php if ($editing_self): ?>
        <small class="form-text text-muted">You can't change your own access level.</small>
        <?php endif; ?>
    </div>

    <div class="col-sm-12 mt-2" id="user-view-toggles">
        <label>Visibility for the 'User' role</label>
        <div class="form-group">
            <div class="icheck-primary d-inline-block mr-4">
                <input type="checkbox" name="can_view_static" id="can_view_static" value="1" <?php echo ($edit && !empty($user['can_view_static'])) ? "checked": "" ; ?>>
                <label for="can_view_static">Can view static qr codes</label>
            </div>
            <div class="icheck-primary d-inline-block">
                <input type="checkbox" name="can_view_dynamic" id="can_view_dynamic" value="1" <?php echo ($edit && !empty($user['can_view_dynamic'])) ? "checked": "" ; ?>>
                <label for="can_view_dynamic">Can view dynamic qr codes</label>
            </div>
            <small class="form-text text-muted">Only applies to the 'User' type. Reports/statistics are always visible for 'User'.</small>
        </div>
    </div>

    <script>
        (function () {
            var typeRadios = document.querySelectorAll('input[name="type"]');
            var toggles = document.getElementById('user-view-toggles');

            function updateToggleVisibility() {
                var userSelected = document.getElementById('type-user').checked;
                toggles.style.display = userSelected ? '' : 'none';
            }

            typeRadios.forEach(function (radio) {
                radio.addEventListener('change', updateToggleVisibility);
            });

            updateToggleVisibility();
        })();
    </script>
    <?php else: ?>
    <!-- An 'admin' can only create/manage their own read-only 'user' accounts. -->
    <input type="hidden" name="type" value="user">

    <div class="col-sm-12 mt-2">
        <label>Visibility for this user</label>
        <div class="form-group">
            <div class="icheck-primary d-inline-block mr-4">
                <input type="checkbox" name="can_view_static" id="can_view_static" value="1" <?php echo ($edit && !empty($user['can_view_static'])) ? "checked": "" ; ?>>
                <label for="can_view_static">Can view static qr codes</label>
            </div>
            <div class="icheck-primary d-inline-block">
                <input type="checkbox" name="can_view_dynamic" id="can_view_dynamic" value="1" <?php echo ($edit && !empty($user['can_view_dynamic'])) ? "checked": "" ; ?>>
                <label for="can_view_dynamic">Can view dynamic qr codes</label>
            </div>
            <small class="form-text text-muted">Reports/statistics are always visible for this account.</small>
        </div>
    </div>
    <?php endif; ?>

    <?php if($edit) { ?>
        <input type="hidden" name="id" value="<?php echo $user['id'];?>"/>
        <input type="hidden" name="edit" value="true"/>
    <?php } ?>
</fieldset>
