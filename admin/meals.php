<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_layout.php';

$message = '';
$error = '';
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $meal_id = (int) ($_POST['meal_id'] ?? 0);
    $meal_name = trim($_POST['meal_name'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);

    if ($action === 'delete') {
        $statement = $conn->prepare('DELETE FROM meals WHERE meal_id = ?');
        $statement->bind_param('i', $meal_id);
        if ($statement->execute()) {
            $message = 'Meal deleted successfully.';
        } else {
            $error = 'This meal is being used by a booking and cannot be deleted.';
        }
    } elseif ($meal_name === '' || $price < 0) {
        $error = 'Meal name is required and price cannot be negative.';
    } elseif ($action === 'update') {
        $statement = $conn->prepare('UPDATE meals SET meal_name = ?, price = ? WHERE meal_id = ?');
        $statement->bind_param('sdi', $meal_name, $price, $meal_id);
        $message = $statement->execute() ? 'Meal updated successfully.' : 'Could not update the meal.';
    } else {
        $statement = $conn->prepare('INSERT INTO meals (meal_name, price) VALUES (?, ?)');
        $statement->bind_param('sd', $meal_name, $price);
        $message = $statement->execute() ? 'Meal added successfully.' : 'Could not add the meal.';
    }
}

if (isset($_GET['edit'])) {
    $meal_id = (int) $_GET['edit'];
    $statement = $conn->prepare('SELECT meal_id, meal_name, price FROM meals WHERE meal_id = ?');
    $statement->bind_param('i', $meal_id);
    $statement->execute();
    $editing = $statement->get_result()->fetch_assoc();
}

$meals = admin_query($conn, 'SELECT meal_id, meal_name, price FROM meals ORDER BY meal_name');
admin_header('Meals', 'meals');
?>
<?php if ($message !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<section class="panel" style="margin-bottom: 20px">
    <div class="panel-heading"><h2><?= $editing ? 'Edit meal' : 'Add a meal' ?></h2><?php if ($editing): ?><a class="text-link" href="meals.php">Cancel edit</a><?php endif; ?></div>
    <form method="post">
        <div class="form-grid">
            <div><label for="meal_name">Meal name</label><input id="meal_name" name="meal_name" value="<?= htmlspecialchars($editing['meal_name'] ?? '') ?>" placeholder="e.g. Vegetarian meal" required></div>
            <div><label for="price">Price</label><input id="price" name="price" type="number" min="0" step="0.01" value="<?= htmlspecialchars($editing['price'] ?? '0') ?>" placeholder="0.00" required></div>
        </div>
        <?php if ($editing): ?><input type="hidden" name="action" value="update"><input type="hidden" name="meal_id" value="<?= (int) $editing['meal_id'] ?>"><?php endif; ?>
        <div class="form-actions"><button class="button button-primary" type="submit"><?= $editing ? 'Save changes' : 'Add meal' ?></button></div>
    </form>
</section>

<section class="panel"><div class="panel-heading"><h2>Meal menu</h2><span class="muted"><?= $meals ? $meals->num_rows : 0 ?> records</span></div>
<?php if ($meals && $meals->num_rows > 0): ?><div class="table-wrap"><table class="data-table"><thead><tr><th>Meal</th><th>Price</th><th>Actions</th></tr></thead><tbody>
<?php while ($meal = $meals->fetch_assoc()): ?><tr><td><strong><?= htmlspecialchars($meal['meal_name']) ?></strong></td><td>₹<?= number_format((float) $meal['price'], 2) ?></td><td><div class="action-links"><a href="?edit=<?= (int) $meal['meal_id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this meal?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="meal_id" value="<?= (int) $meal['meal_id'] ?>"><button class="button button-danger" type="submit">Delete</button></form></div></td></tr><?php endwhile; ?>
</tbody></table></div><?php else: ?><div class="empty-state">No meals have been added yet.</div><?php endif; ?></section>
<?php admin_footer(); ?>