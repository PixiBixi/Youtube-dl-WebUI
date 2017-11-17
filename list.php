<?php
require_once 'vendor/autoload.php';
require_once 'config/config.php';

$session = Session::getInstance();
$file = new FileHandler;

if (!$session->is_logged_in()) {
    header("Location: login.php");
}

if (isset($_GET['type']) && !empty($_GET['type'])) {
    $t = $_GET['type'];
    if ($t === 'v') {
        $type = "video ";
        $mime = "video/mp4";
        $files = $file->listVideos();
    } elseif ($t === 'm') {
        $type = "audio ";
        $mime = "audio/mpeg";
        $files = $file->listMusics();
    }
}

if ($session->is_logged_in() && isset($_GET["delete"])) {
    $file->delete($_GET["delete"], $t);
    header("Location: list.php?type=".$t);
}

require_once 'views/header.php';
?>
		<div class="container">
		<?php if (!empty($files)): ?>
			<h2>List of available <?php echo $type ?> :</h2>
			<table class="table table-striped table-hover ">
				<thead>
					<tr>
						<th style="min-width:300px; height:35px">Title</th>
						<th style="min-width:80px">Size</th>
                        <th style="min-width:110px">Delete link</th>
						<th style="min-width:220px">Stream</th>
					</tr>
				</thead>
				<tbody>
    			<?php
                $i = 0;
                foreach ($files as $f): ?>
                    <tr>
                        <td><a href="<?php echo $config['outputFolder'].'/'.$f['name']; ?>" download>"<?php echo $f["name"]; ?>"</a></td>
                        <td><?php echo $f["size"]; ?></td>
                        <td><a href="./list.php?delete=<?php echo $i; ?>&type=<?php echo $t; ?>" class="btn btn-danger btn-sm">Delete</a></td>
                        <td>
                            <div>
                                <<?= ${type} ?>controls width="320" height="240" >
                                    <source type="<?=${mime} ?>" src="<?php echo $config['outputFolder'].'/'.$f['name']; ?>">
                                <\/<?= ${type} ?>>
                            </div>
                        </td>
                    </tr>
                <?php $i++; ?>
                <?php endforeach; ?>
				</tbody>
			</table>
			<br/>
			<br/>
        <?php else: ?> 
            <?php if (isset($t) && ($t === 'v' || $t === 'm')): ?>
                <br><div class="alert alert-warning" role="alert">No <?= $type;  ?> !</div>
            <?php else: ?>
                <br><div class="alert alert-warning" role="alert">No such type !</div>
            <?php endif; ?>
			<br/>
        <?php endif; ?>
		</div><!-- End container -->
<?php require 'views/footer.php'; ?>
