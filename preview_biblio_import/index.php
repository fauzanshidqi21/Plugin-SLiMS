<?php
/**
 * @Created by          : Drajat Hasan
 * @Date                : 2021-04-28 12:33:42
 * @File name           : index.php
 */

defined('INDEX_AUTH') OR die('Direct access not allowed!');

// IP based access limitation
require LIB . 'ip_based_access.inc.php';
do_checkIP('smc');
do_checkIP('smc-bibliography');
// start the session
require SB . 'admin/default/session.inc.php';
// set dependency
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';
require SIMBIO.'simbio_GUI/table/simbio_table.inc.php';
require SIMBIO.'simbio_GUI/form_maker/simbio_form_table_AJAX.inc.php';
require SIMBIO.'simbio_GUI/paging/simbio_paging.inc.php';
require SIMBIO.'simbio_FILE/simbio_file_upload.inc.php';
// end dependency

// privileges checking
$can_read = utility::havePrivilege('bibliography', 'r');

if (!$can_read) {
    die('<div class="errorBox">' . __('You are not authorized to view this section') . '</div>');
}

function httpQuery($query = [])
{
    return http_build_query(array_unique(array_merge($_GET, $query)));
}

$page_title = 'Preview Import';

// Reset preview
if (isset($_GET['backtoImport']))
{
    // Delete files
    unlink($_SESSION['file']);
    // unset session
    unset($_SESSION['file']);
    // redirect to upload area
    echo '<script>$("#mainContent").simbioAJAX("'.$_SERVER['PHP_SELF'].'?'.str_replace('backtoImport=true', '', httpQuery()).'")</script>';
    exit;
}

// upload file
if (isset($_POST['doImport'])) {
    // check for form validity
    if (!$_FILES['importFile']['name']) {
      utility::jsToastr(__('Import Tool'), __('Please select the file to import!'), 'error');
      exit();
    }

    // create upload object
    $upload = new simbio_file_upload();
    // get system temporary directory location
    $temp_dir = sys_get_temp_dir();
    $uploaded_file = SB.'files/cache'.DIRECTORY_SEPARATOR.$_FILES['importFile']['name'];
    unlink($uploaded_file);
    // set max size
    $max_size = $sysconf['max_upload']*1024;
    $upload->setAllowableFormat(['.csv']);
    $upload->setMaxSize($max_size);
    $upload->setUploadDir(SB.'files/cache');
    $upload_status = $upload->doUpload('importFile');
    if ($upload_status != UPLOAD_SUCCESS) {
        utility::jsToastr(__('Import Tool'), __('Upload failed! File type not allowed or the size is more than').($sysconf['max_upload']/1024).' MB', 'error'); //mfc
        exit();
    }
    // uploaded file path
    $uploaded_file = SB.'files/cache'.DIRECTORY_SEPARATOR.$_FILES['importFile']['name'];
    $row_count = 0;

    // Set up temp file name
    $_SESSION['file'] = $uploaded_file;
    // redirect to self
    echo '<script>parent.$("#mainContent").simbioAJAX("'.$_SERVER['PHP_SELF'].'?'.httpQuery().'")</script>';
    exit;
}

/* Action Area */

if (isset($_GET['preview']))
{
    ob_start();
    // max chars in line for file operations
    $max_chars = 1024*100;

    $file = fopen($_SESSION['file'], 'r');
    $n = 0;

    $sep = (isset($_GET['separator'])) ? urldecode($_GET['separator']) : ';';
    $limit = (isset($_GET['limit'])) ? urldecode($_GET['limit']) : 10;

    ?>
    <table class="table table-hover mt-5">
        <thead class="thead-dark">
            <?php 
                 while (!feof($file)) {
                    // get an array of field
                    $field = fgetcsv($file, $max_chars, $sep, "\"");
                    if ($field && in_array($field[0], ['title','item_code'])) {
                        foreach ($field as $column) {
                            if (strlen($column) > 0)
                            {
                                echo '<th class="text-sm">'. ucwords(str_replace('_', ' ', $column)). '</th>';
                            }
                        } 
                    }
                    break;
                }
                ?>
        </thead>
        <?php

        while (!feof($file)) {
        
            // get an array of field
            $field = fgetcsv($file, $max_chars, $sep, "\"");
            if ($field && !in_array($field[0], ['title','item_code'])) {
                echo '<tr start="'.$n.'">';
                foreach ($field as $key => $value) {
                    if ($key >= 15)
                    {
                        echo '<td>'.rtrim(ltrim(str_replace(['><'], ' - ', $value), '<'), '>').'</td>';
                    }
                    else
                    {
                        echo '<td>'.$value.'</td>';
                    }
                }
                echo '</tr>';
                $n++;
            }

            if ($n >= $limit)
            {
                break;
            }
        }
        ?>
    </table>
    <?php
    $content = ob_get_clean();
    // require template
    require SB.'admin/admin_template/notemplate_page_tpl.php';
    exit;
}

/* End Action Area */
if (isset($_SESSION['file'])) {
// Count rows
$count = (count(file($_SESSION['file'], FILE_SKIP_EMPTY_LINES))) - 1;
?>
<div class="menuBox">
    <div class="menuBoxInner importIcon">
        <div class="per_title">
            <h2>Preview Data</h2>
        </div>
        <div class="sub_section">
                <form name="search" action="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery() ?>" target="preview" id="search" method="get" class="form-inline">Pemisah &nbsp;
                    <select onchange="other(this.value)" name="separator" id="templateSep" class="form-control col-md-2">
                        <option value="0">Pemisah</option>
                        <option value=";" selected>Titik Koma (;)</option>
                        <option value=":">Titik Dua (:)</option>
                        <option value=",">Koma (,)</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                    &nbsp;
                    <input type="text" class="form-control d-none" placeholder="Masukan karakter pemisah lain" id="searchOther"/>
                    &nbsp;Batas Tampil&nbsp;
                    <select onchange="otherLimit(this.value)" name="limit" id="limit" class="form-control col-md-2">
                        <?php 
                            for ($i=1; $i <= 20; $i++) { 
                                ?>
                                <option value="<?= $i * 10 ?>"><?= $i * 10 ?></option>
                                <?php
                            }
                        ?>
                        <option value="lainnya">Jumlah Lainnya</option>
                    </select>
                    &nbsp;
                    <input type="text" class="form-control d-none" id="otherLimitValue" placeholder="Masukan jumlah tampil yang lain" id="searchOther"/>
                    &nbsp;
                    <input type="hidden" name="preview" value="true"/>
                    <input type="hidden" name="id" value="<?= $_GET['id'] ?>"/>
                    <input type="hidden" name="mod" value="<?= $_GET['mod'] ?>"/>
                    <input type="submit" id="doSearch" value="<?php echo __('Search'); ?>" class="s-btn btn btn-default"/>
                </form>
        </div>
        <div class="limitWarning infoBox bg-warning d-none">
            Semakin besar nilai <b>Batas Tampil</b> yang dimasukan mungkin akan membuat waktu eksekusi menjadi lebih lama.
        </div>
        <div class="infoBox">
            Anda berada dalam mode <b>preview</b>. 
            <a href="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery(['backtoImport' => 'true']) ?>" class="btn btn-primary">Kembali ke Import</a>&nbsp;
            <a target="_blank" href="<?= SWB ?>plugins/preview_biblio_import/slims_biblio_import_sample.csv" class="notAJAX btn btn-success">Unduh Sample Import Biblio</a>
            <br>
            Jumlah Data <b><?= $count ?></b>
        </div>
    </div>
</div>
<script>
    function other(value) {
        if (value === 'lainnya')
        {
            $('#templateSep').addClass('d-none').removeAttr('name');
            $('#searchOther').attr('name', 'separator').removeClass('d-none');
        }
    }

    function otherLimit(value) {
        if (value === 'lainnya')
        {
            $('.limitWarning').removeClass('d-none');
            $('#limit').addClass('d-none').removeAttr('name');
            $('#otherLimitValue').attr('name', 'limit').removeClass('d-none');
        }
    }
</script>
<iframe name="preview" src="<?= $_SERVER['PHP_SELF'] . '?' . httpQuery(['preview' => 'true']) ?>" style="width: 100%; border: none; height: 100vh"></iframe>
<?php
} else {
    ?>
    <div class="menuBox">
        <div class="menuBoxInner importIcon">
            <div class="per_title">
                <h2>Preview Data</h2>
            </div>
        </div>
    </div>
    <?php
    // create new instance
    $form = new simbio_form_table_AJAX('mainForm', $_SERVER['PHP_SELF'] . '?' .httpQuery(), 'post');
    $form->submit_button_attr = 'name="doImport" value="'.__('Upload').'" class="btn btn-default"';

    // form table attributes
    $form->table_attr = 'id="dataList" class="s-table table"';
    $form->table_header_attr = 'class="alterCell font-weight-bold"';
    $form->table_content_attr = 'class="alterCell2"';

    /* Form Element(s) */
    // csv files
    $str_input  = '<div class="container">';
    $str_input .= '<div class="row">';
    $str_input .= '<div class="custom-file col-6">';
    $str_input .= simbio_form_element::textField('file', 'importFile','','class="custom-file-input"');
    $str_input .= '<label class="custom-file-label" for="customFile">Choose file</label>';
    $str_input .= '</div>';
    $str_input .= '<div class="col">';
    $str_input .= '<div class="mt-2">Maximum '.$sysconf['max_upload'].' KB</div>';
    $str_input .= '</div>';
    $str_input .= '</div>';
    $str_input .= '</div>';
    $form->addAnything('Preview file .CSV', $str_input);
    // output the form
    echo $form->printOut();
} 
?>