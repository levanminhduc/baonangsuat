<?php
error_reporting(0);
ini_set('display_errors', 0);
require "contdb.php";
require "csrf.php";
require "includes/file-icons.php";
?>

<!DOCTYPE html>
<html lang="en" >
<head>
  <meta http-equiv="refresh" content="3600" content="no-cache">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Xử lý kế hoạch làm việc toàn nhà máy - Quản lý và theo dõi tiến độ công việc theo bộ phận">
  <meta name="keywords" content="xử lý kế hoạch, quản lý bộ phận, công việc nhà máy, theo dõi tiến độ">
  <meta name="author" content="Hệ thống quản lý nhà máy">
  <meta name="robots" content="noindex, nofollow">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>XỬ LÝ KẾ HOẠCH LÀM VIỆC TOÀN NHÀ MÁY</title>
<link rel="stylesheet" href="./style.css?v=<?php echo filemtime('./style.css'); ?>">
<link rel="stylesheet" href="./stylekcs1.css?v=<?php echo filemtime('./stylekcs1.css'); ?>"

<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet" />

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>

<script>
$(document).ready(function() {
    $('.auto-resize-textarea').each(function() {
        var textarea = $(this);

        realTimeVerificationResize(textarea);
    });

    function calculateOptimalHeight(textarea, content) {
        if (!content) return 'auto';

        content = content.trim();
        if (!content) return 'auto';

        var clone = textarea.clone()
            .css({
                'position': 'absolute',
                'visibility': 'hidden',
                'height': 'auto',
                'min-height': 'auto',
                'max-height': 'none',
                'overflow': 'hidden',
                'resize': 'none',
                'top': '-9999px',
                'left': '-9999px'
            })
            .val(content)
            .appendTo('body');

        var requiredHeight = clone[0].scrollHeight;

        var finalHeight = requiredHeight + 1;

        clone.remove();

        return finalHeight + 'px';
    }

    function realTimeVerificationResize(textarea) {
        var content = textarea.val();
        if (!content.trim()) {
            textarea.css('height', 'auto');
            return;
        }

        textarea.css('height', 'auto');

        var requiredHeight = textarea[0].scrollHeight;

        var attempts = 0;
        var maxAttempts = 5;

        while (attempts < maxAttempts) {
            textarea.css({
                'height': requiredHeight + 'px',
                'overflow-y': 'hidden'
            });

            var currentScrollHeight = textarea[0].scrollHeight;
            var currentOffsetHeight = textarea[0].offsetHeight;

            if (currentScrollHeight <= currentOffsetHeight) {
                break;
            } else {
                requiredHeight = currentScrollHeight + 2;
                attempts++;
            }
        }

        if (attempts >= maxAttempts) {
            textarea.css('height', (requiredHeight + 10) + 'px');
        }
    }

    $(window).on('resize orientationchange', function() {
        setTimeout(function() {
            $('.auto-resize-textarea').each(function() {
                var textarea = $(this);
                var content = textarea.val().trim();

                realTimeVerificationResize(textarea);
            });
        }, 150);
    });

    if (window.innerWidth <= 768) {
        setTimeout(function() {
            $('.auto-resize-textarea').each(function() {
                var textarea = $(this);
                realTimeVerificationResize(textarea);
            });
        }, 500);
    }

    loadDepartmentFilter();
    loadMobileDepartmentFilter();

    $('#departmentFilter').on('change', function() {
        var selectedValue = $(this).val();

        if (selectedValue === '') {
            window.location.href = 'index.php';
        } else {
            window.location.href = 'indexdept.php?bophan=' + encodeURIComponent(selectedValue);
        }
    });

    $('#mobileDepartmentFilter').on('change', function() {
        var selectedValue = $(this).val();

        if (selectedValue === '') {
            window.location.href = 'index.php';
        } else {
            window.location.href = 'indexdept.php?bophan=' + encodeURIComponent(selectedValue);
        }
    });
});

function loadDepartmentFilter() {
    $.ajax({
        url: 'get_departments.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var dropdown = $('#departmentFilter');
                var currentDept = '<?php echo isset($_GET["bophan"]) ? addslashes($_GET["bophan"]) : ""; ?>';

                $.each(response.data, function(index, department) {
                    var selected = (department.value === currentDept) ? ' selected' : '';
                    dropdown.append('<option value="' + department.value + '"' + selected + '>' + department.text + '</option>');
                });
            } else {
                console.error('Error loading departments:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
}

function loadMobileDepartmentFilter() {
    $.ajax({
        url: 'get_departments.php',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                var dropdown = $('#mobileDepartmentFilter');
                var currentDept = '<?php echo isset($_GET["bophan"]) ? addslashes($_GET["bophan"]) : ""; ?>';

                $.each(response.data, function(index, department) {
                    var selected = (department.value === currentDept) ? ' selected' : '';
                    dropdown.append('<option value="' + department.value + '"' + selected + '>' + department.text + '</option>');
                });
            } else {
                console.error('Error loading departments:', response.error);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
        }
    });
}

</script>
</head>
<body>
<table width="100%" class="desktop-table">
  <tr style="text-align:center;color:white;">
    <td bgcolor="143583" style="text-align:center;border-right-color:#143583;"><a href="./"><img width="45px" src="img/logoht.png"/></a></td>
    <td colspan = "6" bgcolor="143583" style="font-size:2em;font-weight:bold;text-align:center;line-height: 1.0em;padding-top:5px;">XỬ LÝ KẾ HOẠCH LÀM VIỆC NHÀ MÁY</td><td bgcolor="143583"><a href="addsum.php"><img style="border-radius:5px;" src="img/add.png" width="55px"/></a></td>
  </tr>
   <tr bgcolor="143583" style="font-size:14px;font-weight:bold;text-align:center;color:white;">
        <td style="border: 2px white solid;" width="45px" bgcolor="143583">NGÀY</td>
        <td style="border: 2px white solid;" width="70px" bgcolor="143583">
            <div class="department-header">
                <span class="department-title">BỘ PHẬN</span>
                <select id="departmentFilter">
                    <option value="">BỘ PHẬN</option>
                </select>
            </div>
        </td>
        <td style="border: 2px white solid;" width="70px" bgcolor="143583">NGƯỜI THỰC HIỆN</td>
        <td style="border: 2px white solid;" width="300px" bgcolor="143583">NỘI DUNG CÔNG VIỆC</td>
        <td style="border: 2px white solid;" width="200px" bgcolor="143583">BÁO CÁO TÌNH HÌNH / PHƯƠNG ÁN XỬ LÝ</td>
        <td style="border: 2px white solid;" width="70px" bgcolor="143583">HẠN XỬ LÝ</td>
        <td style="border: 2px white solid;" width="20px" bgcolor="143583">TÌNH<br/>TRẠNG <br/><a href="notdone.php"><img style="border-radius:10px;" src="img/waiting.gif" height="15px"></a></td>
        <td style="border: 2px white solid;" width="45px" bgcolor="143583">NGÀY XỬ LÝ</td>
  </tr>
  
  <?php
        if(isset($_GET["bophan"]) && !empty($_GET["bophan"])){
          $bophan = $_GET["bophan"];
          $sql = "SELECT * FROM lichkehoach WHERE bophan=? ORDER BY tinhtrang ASC, CASE WHEN tinhtrang = 1 THEN ngaythang END DESC, chiutn ASC LIMIT 30";
          $stmt = mysqli_prepare($connect, $sql);
          mysqli_stmt_bind_param($stmt, "s", $bophan);
          mysqli_stmt_execute($stmt);
          $query = mysqli_stmt_get_result($stmt);
        } else {
          $sql = "SELECT * FROM lichkehoach ORDER BY stt DESC LIMIT 30";
          $query = mysqli_query($connect,$sql);
        }
        while($rows = mysqli_fetch_array($query)){
        ?>
        
        <tr style="font-size:1.0em;text-align:center;" data-department="<?php echo htmlspecialchars($rows["bophan"]); ?>">
    <td style="text-align:center;vertical-align:top;padding-top:10px;font-weight:bold;">
    <div class="date-column"><?php echo date("H:i d/m", strtotime($rows['ngaythang'])); ?></div>

    <div class="action-buttons-container">
        <a class="action-button link" style="color:inherit;text-decoration: none !important;"
           href="print.php?stt=<?php echo $rows['stt']; ?>">
            <img src="img/doc.gif" width="21px" title="In dữ liệu">
        </a>

        <?php if ($rows['tinhtrang'] == 0) { ?>
            <a class="action-button" style="color:inherit;text-decoration: none !important;"
               href="edit.php?stt=<?php echo $rows['stt']; ?><?php if(isset($_GET['bophan']) && !empty($_GET['bophan'])) echo '&bophan=' . urlencode($_GET['bophan']); ?>"
               onclick="return confirm('Bạn có muốn chỉnh sửa dữ liệu này?');">
                <img src="img/edit.png" width="24px" title="Chỉnh sửa dữ liệu">
            </a>
        <?php } ?>

        <?php if ($rows['tinhtrang'] == 0) { ?>
            <a class="action-button" onclick="return confirm('Bạn muốn xóa dòng dữ liệu này?');"
                style="color:inherit;text-decoration: none !important;"
                href="del.php?stt=<?php echo $rows['stt']; ?>&bophan=<?php echo urlencode($rows['bophan']); ?>&csrf_token=<?php echo urlencode(getCsrfToken()); ?>">
                <img src="img/del.png" width="24px" title="Xóa dữ liệu">
            </a>
        <?php } ?>
    </div>

</td>
    
    <td style="text-align:center;vertical-align:top;padding-top:10px;font-weight:bold;text-transform:uppercase;">
        <textarea class="auto-resize-textarea department-column" readonly><?php echo htmlspecialchars($rows["bophan"], ENT_QUOTES, 'UTF-8') ?></textarea>
    </td>

    <td style="text-align:center;vertical-align:top;padding-top:10px;font-weight:bold;text-transform:uppercase;max-width:50px;word-wrap: break-word;">
        <textarea class="auto-resize-textarea person-column" readonly><?php echo htmlspecialchars($rows["mahang"], ENT_QUOTES, 'UTF-8') ?></textarea>
    </td>

    <td style="text-align:left;">
    <textarea class="auto-resize-textarea" readonly wrap="hard"><?php echo htmlspecialchars(trim($rows["vuongmac"]), ENT_QUOTES, 'UTF-8'); ?></textarea>
    <div id="imageContainer" class="image-container">
    <?php
    if ($rows["image_url"]) {
        $image_urls = explode(';', $rows["image_url"]);
        foreach ($image_urls as $image_url) {
            $safe_image_url = htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8');
            echo "<div class='main-image'>
                    <a href='$safe_image_url' class='lightbox-link' data-lightbox='task-images' data-title='Ảnh chi tiết'>
                        <img src='$safe_image_url' class='current-image' />
                    </a>
                  </div>";
        }
    }
    ?>
</div>

    <div class="file-attachment-container">
    <?php
    if ($rows["file_attachment_url"]) {
        $file_urls = explode(';', $rows["file_attachment_url"]);
        foreach ($file_urls as $file_url) {
            $file_name = basename($file_url);
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            $icon_data = getFileIcon($file_ext);

            $safe_file_url = htmlspecialchars($file_url, ENT_QUOTES, 'UTF-8');
            $safe_file_name = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
            $safe_file_ext = htmlspecialchars($file_ext, ENT_QUOTES, 'UTF-8');

            echo "<a href='$safe_file_url' class='file-attachment-item' target='_blank' download>
                    <img src='$icon_data' class='file-icon' alt='$safe_file_ext'>
                    <span class='file-name' title='$safe_file_name'>$safe_file_name</span>
                  </a>";
        }
    }
    ?>
</div>
</td>

    <td style="text-align:left;">
        <textarea class="auto-resize-textarea" readonly wrap="hard"><?php echo htmlspecialchars(trim($rows["bpkhacphuc"]), ENT_QUOTES, 'UTF-8'); ?></textarea>
    </td>

    <?php
    $today = new DateTime();
    $today->setTime(0, 0, 0);

    if (isset($rows["chiutn"]) && !empty($rows["chiutn"])) {
        $deadline = new DateTime($rows["chiutn"]);
        $deadline->setTime(0, 0, 0);

        if ($deadline < $today) {
            $color = "color: red; font-weight: bold;";
        } else {
            $color = "color: black; font-weight: normal;";
        }
    } else {
        $color = "color: black; font-weight: normal;";
    }
?>

<td style="text-align:left;vertical-align:top;padding-top:10px; <?php echo $color; ?>">
    <?php 
        if (isset($rows["chiutn"]) && !empty($rows["chiutn"])) {
            $date = new DateTime($rows["chiutn"]);
            echo $date->format('d/m/Y');
        } else {
            echo "Chưa có ngày đến hạn";
        }
    ?>
</td>

    <td style="text-align:center;vertical-align:top;padding-top:10px;font-weight:bold; background-color:
                <?php
                if ($rows["tinhtrang"] == 0 && isset($rows["chiutn"]) && !empty($rows["chiutn"])) {
                    $deadline = new DateTime($rows["chiutn"]);
                    $deadline->setTime(0, 0, 0);
                    if ($deadline > $today) {
                        echo "#ffffff";
                    } else {
                        echo "#fea7a7";
                    }
                } elseif ($rows["tinhtrang"] == 1) {
                    echo "#c0fea7";
                } else {
                    echo "#fea7a7";
                }
                ?>
            ">
        <a href="checked.php?stt=<?php echo $rows["stt"]?>&bophan=<?php echo urlencode($rows["bophan"])?>&csrf_token=<?php echo urlencode(getCsrfToken()); ?>" onclick="return confirm('Bạn đã hoàn thành công việc này?');">
            <?php
            if ($rows["tinhtrang"] == 0 && isset($rows["chiutn"]) && !empty($rows["chiutn"])) {
                $deadline = new DateTime($rows["chiutn"]);
                $deadline->setTime(0, 0, 0);
                if ($deadline > $today) {
                    echo "<img style='vertical-align: middle;' src='img/loading.gif' width='70px'/>";
                } else {
                    echo "<img style='vertical-align: middle;' src='img/nok.png' width='30px'/>";
                }
            } elseif ($rows["tinhtrang"] == 1) {
                echo "<img style='vertical-align: middle;' src='img/ok.png' width='30px'/>";
            } else {
                echo "<img style='vertical-align: middle;' src='img/nok.png' width='30px'/>";
            }
            ?>
        </a>
    </td>

    <td style="text-align:left;vertical-align:top;padding-top:10px;">
        <?php if($rows["ghichu"]==null){echo "Chưa xử lý";} else {echo htmlspecialchars($rows["ghichu"], ENT_QUOTES, 'UTF-8');} ?>
    </td>
</tr>

  <?php }?>
</table>

<div class="mobile-cards-container">
    <div style="text-align:center; background:#143583; color:white; padding:15px; border-radius:8px; margin-bottom:16px;">
        <div style="display:flex; align-items:center; justify-content:space-between;">
            <a href="./"><img width="35px" src="img/logoht.png" /></a>
            <div style="flex:1; font-size:1.2em; font-weight:bold; margin:0 10px;">
                XỬ LÝ KẾ HOẠCH LÀM VIỆC NHÀ MÁY
            </div>
            <div>
                <a href="addsum.php"><img style="border-radius:5px;" src="img/add.png" width="30px" /></a>
            </div>
        </div>
    </div>

    <div class="mobile-department-filter">
        <label for="mobileDepartmentFilter">Lọc theo bộ phận:</label>
        <select id="mobileDepartmentFilter">
            <option value="">Tất cả bộ phận</option>
        </select>
    </div>

    <div id="mobileCardsContent">
        <?php
        if(isset($_GET["bophan"]) && !empty($_GET["bophan"])){
          $bophan = $_GET["bophan"];
          $sql_mobile = "SELECT * FROM lichkehoach WHERE bophan=? ORDER BY tinhtrang ASC, CASE WHEN tinhtrang = 1 THEN ngaythang END DESC, chiutn ASC LIMIT 30";
          $stmt_mobile = mysqli_prepare($connect, $sql_mobile);
          mysqli_stmt_bind_param($stmt_mobile, "s", $bophan);
          mysqli_stmt_execute($stmt_mobile);
          $query = mysqli_stmt_get_result($stmt_mobile);
        } else {
          $sql_mobile = "SELECT * FROM lichkehoach ORDER BY stt DESC LIMIT 30";
          $query = mysqli_query($connect,$sql_mobile);
        }

        while($rows = mysqli_fetch_array($query)){
            $today = new DateTime();
            $today->setTime(0, 0, 0);

            if (isset($rows["chiutn"]) && !empty($rows["chiutn"])) {
                $deadline = new DateTime($rows["chiutn"]);
                $deadline->setTime(0, 0, 0);

                if ($deadline < $today && $rows["tinhtrang"] == 0) {
                    $color = "color: red; font-weight: bold;";
                    $statusClass = "status-overdue";
                } else {
                    $color = "color: black; font-weight: normal;";
                    $statusClass = $rows["tinhtrang"] == 1 ? "status-1" : "status-0";
                }
            } else {
                $color = "color: black; font-weight: normal;";
                $statusClass = $rows["tinhtrang"] == 1 ? "status-1" : "status-0";
            }
        ?>

        <div class="mobile-task-card" data-department="<?php echo htmlspecialchars($rows["bophan"]); ?>">
            <div class="card-status-badge <?php echo $statusClass; ?>">
                <?php
                if ($rows["tinhtrang"] == 0 && isset($rows["chiutn"]) && !empty($rows["chiutn"])) {
                    $deadline = new DateTime($rows["chiutn"]);
                    $deadline->setTime(0, 0, 0);
                    if ($deadline > $today) {
                        echo "<img style='width:20px;height:20px;' src='img/loading.gif' />";
                        echo "<span>Đang xử lý</span>";
                    } else {
                        echo "<img style='width:20px;height:20px;' src='img/nok.png' />";
                        echo "<span>Quá hạn</span>";
                    }
                } elseif ($rows["tinhtrang"] == 1) {
                    echo "<img style='width:20px;height:20px;' src='img/ok.png' />";
                    echo "<span>Hoàn thành</span>";
                } else {
                    echo "<img style='width:20px;height:20px;' src='img/nok.png' />";
                    echo "<span>Chưa xử lý</span>";
                }
                ?>
            </div>

            <div class="card-header">
                <div class="card-date">
                    <span class="time"><?php echo date("H:i", strtotime($rows['ngaythang'])); ?></span>
                    <span class="date"><?php echo date("d/m", strtotime($rows['ngaythang'])); ?></span>
                </div>
                <div class="card-department">
                    <span><?php echo htmlspecialchars($rows["bophan"]); ?></span>
                </div>
            </div>

            <div class="card-content">
                <div class="card-person">
                    <label>Người thực hiện:</label>
                    <span><?php echo htmlspecialchars($rows["mahang"]); ?></span>
                </div>

                <div class="card-work-content">
                    <label>Nội dung công việc:</label>
                    <textarea class="auto-resize-textarea" readonly wrap="hard"><?php echo htmlspecialchars(trim($rows["vuongmac"]), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <?php if (!empty(trim($rows["bpkhacphuc"]))) { ?>
                <div class="card-report">
                    <label>Báo cáo tình hình:</label>
                    <textarea class="auto-resize-textarea" readonly wrap="hard"><?php echo htmlspecialchars(trim($rows["bpkhacphuc"]), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
                <?php } ?>

                <?php if ($rows["image_url"]) { ?>
                <div class="card-images">
                    <label>Hình ảnh:</label>
                    <div class="mobile-gallery">
                        <?php
                        $image_urls = explode(';', $rows["image_url"]);
                        foreach ($image_urls as $image_url) {
                            $safe_image_url = htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8');
                            echo "<a href='$safe_image_url' class='lightbox-link' data-lightbox='mobile-task-images-{$rows["stt"]}' data-title='Ảnh chi tiết'>
                                    <img src='$safe_image_url' alt='Task image' />
                                  </a>";
                        }
                        ?>
                    </div>
                </div>
                <?php } ?>

                <?php if ($rows["file_attachment_url"]) { ?>
                <div class="card-files">
                    <label>File đính kèm:</label>
                    <div class="mobile-files">
                        <?php
                        $file_urls = explode(';', $rows["file_attachment_url"]);
                        foreach ($file_urls as $file_url) {
                            $file_name = basename($file_url);
                            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                            $icon_data = '';
                            switch ($file_ext) {
                                case 'pdf':
                                    $icon_data = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGRlZnM+CjxsaW5lYXJHcmFkaWVudCBpZD0icGRmR3JhZGllbnQiIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPgo8c3RvcCBvZmZzZXQ9IjAlIiBzdHlsZT0ic3RvcC1jb2xvcjojRkY0NDU4O3N0b3Atb3BhY2l0eToxIiAvPgo8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0eWxlPSJzdG9wLWNvbG9yOiNEQzM1NDU7c3RvcC1vcGFjaXR5OjEiIC8+CjwvbGluZWFyR3JhZGllbnQ+CjwvZGVmcz4KPHBhdGggZD0iTTUgNUM0LjQ0NzcyIDUgNCA1LjQ0NzcyIDQgNlYyNkM0IDI2LjU1MjMgNC40NDc3MiAyNyA1IDI3SDIzQzIzLjU1MjMgMjcgMjQgMjYuNTUyMyAyNCAyNlYxMkwxNyA1SDVaIiBmaWxsPSJ1cmwoI3BkZkdyYWRpZW50KSIgc3Ryb2tlPSIjQjkxQzI4IiBzdHJva2Utd2lkdGg9IjAuNSIvPgo8cGF0aCBkPSJNMTcgNVYxMkgyNCIgZmlsbD0iI0I5MUMyOCIvPgo8dGV4dCB4PSIxMy41IiB5PSIyMCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjgiIGZvbnQtd2VpZ2h0PSJib2xkIiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+UERGPC90ZXh0Pgo8L3N2Zz4=';
                                    break;
                                case 'doc':
                                case 'docx':
                                    $icon_data = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGRlZnM+CjxsaW5lYXJHcmFkaWVudCBpZD0iZG9jR3JhZGllbnQiIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPgo8c3RvcCBvZmZzZXQ9IjAlIiBzdHlsZT0ic3RvcC1jb2xvcjojNDA5NkZGO3N0b3Atb3BhY2l0eToxIiAvPgo8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0eWxlPSJzdG9wLWNvbG9yOiMyOTY0QjQ7c3RvcC1vcGFjaXR5OjEiIC8+CjwvbGluZWFyR3JhZGllbnQ+CjwvZGVmcz4KPHBhdGggZD0iTTUgNUM0LjQ0NzcyIDUgNCA1LjQ0NzcyIDQgNlYyNkM0IDI2LjU1MjMgNC40NDc3MiAyNyA1IDI3SDIzQzIzLjU1MjMgMjcgMjQgMjYuNTUyMyAyNCAyNlYxMkwxNyA1SDVaIiBmaWxsPSJ1cmwoI2RvY0dyYWRpZW50KSIgc3Ryb2tlPSIjMUY0RTc5IiBzdHJva2Utd2lkdGg9IjAuNSIvPgo8cGF0aCBkPSJNMTcgNVYxMkgyNCIgZmlsbD0iIzFGNEU3OSIvPgo8dGV4dCB4PSIxMy41IiB5PSIyMCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjciIGZvbnQtd2VpZ2h0PSJib2xkIiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+RE9DPC90ZXh0Pgo8L3N2Zz4=';
                                    break;
                                case 'xls':
                                case 'xlsx':
                                    $icon_data = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGRlZnM+CjxsaW5lYXJHcmFkaWVudCBpZD0ieGxzR3JhZGllbnQiIHgxPSIwJSIgeTE9IjAlIiB4Mj0iMTAwJSIgeTI9IjEwMCUiPgo8c3RvcCBvZmZzZXQ9IjAlIiBzdHlsZT0ic3RvcC1jb2xvcjojMzRBODUzO3N0b3Atb3BhY2l0eToxIiAvPgo8c3RvcCBvZmZzZXQ9IjEwMCUiIHN0eWxlPSJzdG9wLWNvbG9yOiMxRDcwNDU7c3RvcC1vcGFjaXR5OjEiIC8+CjwvbGluZWFyR3JhZGllbnQ+CjwvZGVmcz4KPHBhdGggZD0iTTUgNUM0LjQ0NzcyIDUgNCA1LjQ0NzcyIDQgNlYyNkM0IDI2LjU1MjMgNC40NDc3MiAyNyA1IDI3SDIzQzIzLjU1MjMgMjcgMjQgMjYuNTUyMyAyNCAyNlYxMkwxNyA1SDVaIiBmaWxsPSJ1cmwoI3hsc0dyYWRpZW50KSIgc3Ryb2tlPSIjMTU1NzM2IiBzdHJva2Utd2lkdGg9IjAuNSIvPgo8cGF0aCBkPSJNMTcgNVYxMkgyNCIgZmlsbD0iIzE1NTczNiIvPgo8dGV4dCB4PSIxMy41IiB5PSIyMCIgZm9udC1mYW1pbHk9IkFyaWFsLCBzYW5zLXNlcmlmIiBmb250LXNpemU9IjciIGZvbnQtd2VpZ2h0PSJib2xkIiBmaWxsPSJ3aGl0ZSIgdGV4dC1hbmNob3I9Im1pZGRsZSI+WExTPC90ZXh0Pgo8L3N2Zz4=';
                                    break;
                                default:
                                    $icon_data = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIHZpZXdCb3g9IjAgMCAzMiAzMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPGRlZnM+CjxsaW5lYXJHcmFkaWVudCBpZD0iZGVmYXVsdEdyYWRpZW50IiB4MT0iMCUiIHkxPSIwJSIgeDI9IjEwMCUiIHkyPSIxMDAlIj4KPHN0b3Agb2Zmc2V0PSIwJSIgc3R5bGU9InN0b3AtY29sb3I6Izg2OTA5QztzdG9wLW9wYWNpdHk6MSIgLz4KPHN0b3Agb2Zmc2V0PSIxMDAlIiBzdHlsZT0ic3RvcC1jb2xvcjojNkM3NTdEO3N0b3Atb3BhY2l0eToxIiAvPgo8L2xpbmVhckdyYWRpZW50Pgo8L2RlZnM+CjxwYXRoIGQ9Ik01IDVDNC40NDc3MiA1IDQgNS40NDc3MiA0IDZWMjZDNCAyNi41NTIzIDQuNDQ3NzIgMjcgNSAyN0gyM0MyMy41NTIzIDI3IDI0IDI2LjU1MjMgMjQgMjZWMTJMMTcgNUg1WiIgZmlsbD0idXJsKCNkZWZhdWx0R3JhZGllbnQpIiBzdHJva2U9IiM0OTUwNTciIHN0cm9rZS13aWR0aD0iMC41Ii8+CjxwYXRoIGQ9Ik0xNyA1VjEySDI0IiBmaWxsPSIjNDk1MDU3Ii8+Cjwvc3ZnPg==';
                            }

                            $safe_file_url = htmlspecialchars($file_url, ENT_QUOTES, 'UTF-8');
                            $safe_file_name = htmlspecialchars($file_name, ENT_QUOTES, 'UTF-8');
                            $safe_file_ext = htmlspecialchars($file_ext, ENT_QUOTES, 'UTF-8');
                            echo "<a href='$safe_file_url' class='file-attachment-item' target='_blank' download>
                                    <img src='$icon_data' class='file-icon' alt='$safe_file_ext'>
                                    <span class='file-name' title='$safe_file_name'>$safe_file_name</span>
                                  </a>";
                        }
                        ?>
                    </div>
                </div>
                <?php } ?>
            </div>

            <div class="card-footer">
                <div class="card-deadline">
                    <label>Hạn xử lý:</label>
                    <span class="deadline-date <?php echo ($deadline < $today && $rows["tinhtrang"] == 0) ? 'overdue' : ''; ?>" style="<?php echo $color; ?>">
                        <?php
                        if (isset($rows["chiutn"]) && !empty($rows["chiutn"])) {
                            $date = new DateTime($rows["chiutn"]);
                            echo $date->format('d/m/Y');
                        } else {
                            echo "Chưa có ngày đến hạn";
                        }
                        ?>
                    </span>
                </div>
                <div class="card-process-date">
                    <label>Ngày xử lý:</label>
                    <span><?php echo $rows["ghichu"] ? htmlspecialchars($rows["ghichu"]) : "Chưa xử lý"; ?></span>
                </div>

                <div class="card-actions">
                    <a href="print.php?stt=<?php echo $rows["stt"]; ?>" class="mobile-action-btn">
                        <img src="img/doc.gif" alt="Print" />
                        <span>In</span>
                    </a>

                    <?php if ($rows['tinhtrang'] == 0) { ?>
                        <a href="edit.php?stt=<?php echo $rows['stt']; ?><?php if(isset($_GET['bophan']) && !empty($_GET['bophan'])) echo '&bophan=' . urlencode($_GET['bophan']); ?>"
                           class="mobile-action-btn"
                           onclick="return confirm('Bạn có muốn chỉnh sửa dữ liệu này?');">
                            <img src="img/edit.png" alt="Edit" />
                            <span>Sửa</span>
                        </a>
<a href="del.php?stt=<?php echo $rows['stt']; ?>&bophan=<?php echo urlencode($rows['bophan']); ?>&csrf_token=<?php echo urlencode(getCsrfToken()); ?>"
   class="mobile-action-btn delete"
   onclick="return confirm('Bạn muốn xóa dữ liệu này?');">
    <img src="img/del.png" alt="Delete" />
    <span>Xóa</span>
</a>

<a href="checked.php?stt=<?php echo $rows['stt']; ?>&bophan=<?php echo urlencode($rows['bophan']); ?>&csrf_token=<?php echo urlencode(getCsrfToken()); ?>"
   class="mobile-action-btn complete"
   onclick="return confirm('Bạn đã hoàn thành công việc này?');">
    <img src="img/ok.png" alt="Complete" />
    <span>Hoàn thành</span>
</a>
                    <?php } ?>
                </div>
            </div>
        </div>

        <?php } ?>
    </div>
</div>

<?php mysqli_close($connect); ?>

</body>
</html>
