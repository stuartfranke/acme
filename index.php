<?php
ob_start();

require_once __DIR__ . DIRECTORY_SEPARATOR . 'initialisation.php';

use Acme\Tracker\Objects\Issues;

$issues = new Issues();
$issuesData = [];
$labelData = [];
$loginUrl = '';
$logoutUrl = '';
$createUrl = '';

if (!empty($_SESSION['access_token'])) {
    $issuesData = $issues->getIssues();
    $labelData = $issues->getIssueLabels();
    $logoutUrl = $helper->getLogoutUrl();
    $createUrl = $helper->getCreateUrl();
} else {
    $loginUrl = $helper->getLoginUrl();
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <title>Acme Tracker</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css"
          integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar">
            <div class="sidebar-sticky">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <?php if (empty($_SESSION['access_token'])): ?>
                            <a class="nav-link" href="<?php echo $loginUrl; ?>">Login</a>
                        <?php else: ?>
                            <a class="nav-link" href="<?php echo $logoutUrl; ?>">Logout</a>
                        <?php endif; ?>
                    </li>
                    <?php if (!empty($_SESSION['access_token'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="create-link">Create Issue</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>

        <main role="main" class="col-md-9 ml-sm-auto col-lg-10 px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1>Dashboard</h1>
            </div>
            <div class="row">
                <?php if (count($helper->getMessages('success')) > 0): ?>
                    <div id="success-alert" class="alert alert-success" role="alert">
                        <h4 class="alert-heading">Success</h4>
                        <ul class="list-group">
                            <?php foreach ($helper->getMessages('success') as $successMessage): ?>
                                <li class="list-group-item list-group-item-success"><?php echo $successMessage; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php
                    $helper->clearMessages('success');
                endif; ?>
                <?php if (count($helper->getMessages('error')) > 0): ?>
                    <div id="error-alert" class="alert alert-danger" role="alert">
                        <h4 class="alert-heading">Error</h4>
                        <ul class="list-group">
                            <?php foreach ($helper->getMessages('error') as $errorMessage): ?>
                                <li class="list-group-item list-group-item-danger"><?php echo $errorMessage; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <span id="show-form"
                              data-show-form="<?php echo isset($_SESSION['show_form']) ? (int)$_SESSION['show_form'] : 0; ?>"></span>
                        <?php unset($_SESSION['show_form']); ?>
                    </div>
                    <?php
                    $helper->clearMessages('error');
                endif; ?>
            </div>
            <?php if (empty($_SESSION['access_token'])): ?>
                <div>
                    <h2>Login required</h2>
                    <p>Please log in to continue</p>
                    <a href="<?php echo $loginUrl; ?>" class="btn btn-primary">Login</a>
                </div>
            <?php else: ?>
                <div id="form-container" class="mb-3 d-none">
                    <h2>Create Issue</h2>
                    <form action="<?php echo $createUrl; ?>" method="post">
                        <div class="form-group">
                            <label for="title">Title</label>
                            <input type="text" class="form-control" id="title" name="title" placeholder="Enter title"
                                   required value="<?php echo isset($_SESSION['form_data']['title']) ? $_SESSION['form_data']['title'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="body">Description</label>
                            <textarea class="form-control" id="body" name="body" rows="3" required><?php echo isset($_SESSION['form_data']['body']) ? $_SESSION['form_data']['body'] : ''; ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="client">Client</label>
                            <select class="form-control" id="client" name="client" required>
                                <option value="">Select client</option>
                                <?php
                                $clientLabels = $issues->parseLabelData(
                                    $labelData,
                                    Issues::LABEL_CLIENT,
                                    false
                                );
                                $rawClientLabels = $issues->parseLabelData(
                                    $labelData,
                                    Issues::LABEL_CLIENT,
                                    true
                                );

                                if ($clientLabels > 0 && count($clientLabels) === count($rawClientLabels)) {
                                    array_map(function ($clientLabel, $rawClientLabel) {
                                        echo '<option value="' . $rawClientLabel . '" ' . (isset($_SESSION['form_data']['client']) && $_SESSION['form_data']['client'] === $rawClientLabel ? 'selected' : '') .'>' . $clientLabel . '</option>';
                                    }, $clientLabels, $rawClientLabels);
                                    unset($clientLabels, $rawClientLabels);
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="priority">Priority</label>
                            <select class="form-control" id="priority" name="priority" required>
                                <option value="">Select priority</option>
                                <?php
                                $priorityLabels = $issues->parseLabelData(
                                    $labelData,
                                    Issues::LABEL_PRIORITY,
                                    false
                                );
                                $rawPriorityLabels = $issues->parseLabelData(
                                    $labelData,
                                    Issues::LABEL_PRIORITY,
                                    true
                                );

                                if ($priorityLabels > 0 && count($priorityLabels) === count($rawPriorityLabels)) {
                                    array_map(function ($priorityLabel, $rawPriorityLabel) {
                                        echo '<option value="' . $rawPriorityLabel . '" ' . (isset($_SESSION['form_data']['priority']) && $_SESSION['form_data']['priority'] === $rawPriorityLabel ? 'selected' : '') .'>' . $priorityLabel . '</option>';
                                    }, $priorityLabels, $rawPriorityLabels);
                                    unset($priorityLabels, $rawPriorityLabels);
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="type">Type</label>
                            <select class="form-control" id="type" name="type" required>
                                <option value="">Select type</option>
                                <?php
                                $typeLabels = $issues->parseLabelData(
                                    $labelData,
                                    Issues::LABEL_TYPE,
                                    false
                                );
                                $rawTypeLabels = $issues->parseLabelData(
                                    $labelData,
                                    Issues::LABEL_TYPE,
                                    true
                                );

                                if ($typeLabels > 0 && count($typeLabels) === count($rawTypeLabels)) {
                                    array_map(function ($typeLabel, $rawTypeLabel) {
                                        echo '<option value="' . $rawTypeLabel . '" ' . (isset($_SESSION['form_data']['type']) && $_SESSION['form_data']['type'] === $rawTypeLabel ? 'selected' : '') .'>' . $typeLabel . '</option>';
                                    }, $typeLabels, $rawTypeLabels);
                                    unset($typeLabels, $rawTypeLabels);
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" name="submit" value="create" class="btn btn-primary">Submit</button>
                        <a href="#" class="btn btn-secondary" id="hide-form-link">Hide Form</a>
                    </form>
                    <?php unset($_SESSION['form_data']); ?>
                </div>
                <div class="table-responsive">
                    <h2>Issues</h2>
                    <table class="table table-striped table-sm">
                        <thead>
                        <tr>
                            <th>Number</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Client</th>
                            <th>Priority</th>
                            <th>Type</th>
                            <th>Assignees</th>
                            <th>Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!isset($issuesData) || !is_array($issuesData) || count($issuesData) === 0): ?>
                            <tr>
                                <td colspan="8">No issues found</td>
                            </tr>
                        <?php else:
                            foreach ($issuesData as $key => $issue): ?>
                                <tr>
                                    <td><?php echo $issue['number']; ?></td>
                                    <td><?php echo $issue['title']; ?></td>
                                    <td><?php echo $issue['body']; ?></td>
                                    <td><?php echo $issue['client']; ?></td>
                                    <td><?php echo $issue['priority']; ?></td>
                                    <td><?php echo $issue['type']; ?></td>
                                    <td><?php echo $issue['assignees']; ?></td>
                                    <td><?php echo $issue['status']; ?></td>
                                </tr>
                            <?php endforeach;
                        endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js"
        integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo"
        crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
        integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1"
        crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"
        integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM"
        crossorigin="anonymous"></script>
<script>
    function showCreateForm() {
        if ($('#form-container').hasClass('d-none')) {
            $('#form-container').removeClass('d-none').addClass('d-block');
        }
    }

    $('#create-link').click(function () {
        showCreateForm();
    });

    $('#hide-form-link').click(function () {
        if ($('#form-container').hasClass('d-block')) {
            $('#form-container').removeClass('d-block').addClass('d-none');
        }
    });

    if ($('#show-form').length > 0 && $('#show-form').data('show-form') === 1) {
        showCreateForm();
    }
</script>
</body>
</html>
<?php ob_end_flush(); ?>
