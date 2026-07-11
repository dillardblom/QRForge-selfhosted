<?php $is_readonly_user = $_SESSION['type'] === 'user'; ?>
<div class="row">
    <?php if (!$is_readonly_user): ?>
    <div class="col-12" id="bulk-action-div" style="display: none;">
        <div id="err-msg"></div>
        <div class="bulk-action-wrapper">
            <form id="bulk-action" action="bulk_action.php" method="POST">
<?php echo csrf_field(); ?>
                <div class="col-sm-12 mb-2" style="margin-left: 10px">
                    <div class="row">
                        <div class="col-5 col-md-2">
                            <div class="input-group">
                                <select name="action" class="form-control">
                                    <option value="download" selected >Download</option>
                                    <option value="delete">Delete</option>
                                </select>
                                <input type="hidden" name="type" value="dynamic">
                                <button type="submit" class="btn btn-primary">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-12">
        <div class="card">
            <div class="card-body table-responsive p-0">
      <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <?php if (!$is_readonly_user): ?>
                <th><input type="checkbox" name="bulk-select" value="1"></th>
                <?php endif; ?>
                <th>ID</th>
                <th>Owner</th>
                <th>Filename</th>
                <th>Unique redirect identifier</th>
                <th>URL</th>
                <th>Qr code</th>
                <th>Scan</th>
                <th>Status</th>
                <th>Operations</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
            <tr>
                <?php if (!$is_readonly_user): ?>
                <td><input type="checkbox" name="action[]" value="<?=$row['id']?>" onchange="updateBulkActionVisibility()"></td>
                <?php endif; ?>
                <td><?php echo $row['id']; ?></td>
                <td>
                    <?php
                    if(!isset($row['id_owner']))
                        echo "";
                    else {
                        require_once BASE_PATH . '/lib/Users/Users.php';
                        $users = new Users();
                        $user = $users->getUser($row['id_owner']);
                        if($user !== NULL)
                            echo $user["username"];
                        else
                            echo "";
                    }
                    ?>
                </td>
                <td><?php echo htmlspecialchars($row['filename']); ?></td>
                <td><?php echo htmlspecialchars($row['identifier']); ?></td>
                <td><?php echo htmlspecialchars($row['link']); ?></td>
                <td>
                    <a href="#" class="qr-thumb-link" data-toggle="modal" data-target="#preview-modal"
                       data-qr-src="qrcode_image.php?type=dynamic&id=<?php echo $row['id']; ?>"
                       data-qr-name="<?php echo htmlspecialchars($row['filename']); ?>">
                        <img src="qrcode_image.php?type=dynamic&id=<?php echo $row['id']; ?>" class="qr-thumb-img" alt="QR code for <?php echo htmlspecialchars($row['filename']); ?>">
                    </a>
                </td>
                <td><?php echo htmlspecialchars($row['scan']); ?></td>
                <td><?php echo htmlspecialchars($row['state']); ?></td>
                <td>
                    <?php if (!$is_readonly_user): ?>
                    <!-- EDIT -->
                    <a href="dynamic_qrcode.php?edit=true&id=<?php echo $row['id']; ?>" class="btn btn-primary"><i class="fas fa-edit"></i></a>

                    <!-- DELETE -->
                    <a
                            class="btn btn-danger delete_btn"
                            data-toggle="modal"
                            data-target="#delete-modal"
                            data-del_id="<?php echo $row["id"];?>"
                    ><i class="fas fa-trash"></i></a>
                    <?php endif; ?>
                    <!-- DOWNLOAD -->
                    <a href="qrcode_image.php?type=dynamic&id=<?php echo $row['id']; ?>&download=1" class="btn btn-primary"><i class="fa fa-download"></i></a>

                    <!-- COPY TO CLIPBOARD -->
                    <button type="button" class="btn btn-secondary copy-qr-btn" data-qr-src="qrcode_image.php?type=dynamic&id=<?php echo $row['id']; ?>" title="Copy image to clipboard"><i class="fa fa-copy"></i></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
   </div><!-- /.Card body -->
   
   <div class="card-footer clearfix">
       <?php echo paginationLinks($page, $total_pages, 'dynamic_qrcodes.php'); ?>
       </div><!-- /.Card footer -->
       
        </div><!-- /.Card -->
    </div><!-- /.col -->
</div><!-- /.row -->

<?php if (!$is_readonly_user): ?>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="delete-modal" role="dialog">
    <div class="modal-dialog">
        <form action="dynamic_qrcode.php" method="POST">
<?php echo csrf_field(); ?>
            <!-- Modal content -->

            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title">Confirm</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="del_id" id="del_id" value="">
                    <p>Are you sure you want to delete this row? Proceeding with the cancellation it will no longer be possible to recover the unique identifier and you will delete the created QR code from the server</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save changes</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- /.Delete Confirmation Modal -->
<?php endif; ?>

<!-- Qr Code Preview Modal -->
<div class="modal fade" id="preview-modal" role="dialog">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="preview-modal-title">QR code preview</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <img id="preview-modal-img" src="" alt="" style="max-width:100%;max-height:70vh;">
            </div>
        </div>
    </div>
</div>
<!-- /.Qr Code Preview Modal -->

<script>
    document.querySelectorAll('.qr-thumb-link').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById('preview-modal-img').src = link.getAttribute('data-qr-src');
            document.getElementById('preview-modal-title').textContent = link.getAttribute('data-qr-name');
        });
    });
</script>

<script>
    const deleteButtons = document.querySelectorAll('.delete_btn');

    deleteButtons.forEach(button => {
        button.addEventListener('click', function () {
            document.getElementById('del_id').value = button.getAttribute('data-del_id');

            const deleteModal = document.querySelector('#delete-modal');
            deleteModal.style.display = 'block';
        });
    });
</script>

<script>
    function updateBulkActionVisibility() {
        const checkboxes = document.querySelectorAll('input[name="action[]"]');
        const bulkActionDiv = document.getElementById('bulk-action-div');

        const selectedCheckboxes = Array.from(checkboxes).filter(checkbox => checkbox.checked);

        if (selectedCheckboxes.length > 0) {
            bulkActionDiv.style.display = 'block';
        } else {
            bulkActionDiv.style.display = 'none';
        }
    }

    updateBulkActionVisibility();
</script>