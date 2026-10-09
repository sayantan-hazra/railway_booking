<?php
/**
 * Admin - Meal management (add / edit / delete).
 *
 * Meals are offered on trains and can be chosen per passenger during
 * booking. Deletion is refused while a meal is still assigned to a train
 * (train_meals) or referenced by a booking.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/admin_layout.php';

// Flash messages and the meal currently being edited (null = add mode).
$message = '';
$error = '';
$editing = null;

// Handle add / update / delete form submissions.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $meal_id = (int) ($_POST['meal_id'] ?? 0);
    $meal_name = trim($_POST['meal_name'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);

    // Delete: refuse while the meal is still assigned to a train or booking.
    if ($action === 'delete') {
        $usageStatement = $conn->prepare(
            'SELECT
                (SELECT COUNT(*) FROM train_meals WHERE meal_id = ?) +
                (SELECT COUNT(*) FROM bookings WHERE meal_id = ?) AS total'
        );
        $usageStatement->bind_param('ii', $meal_id, $meal_id);
        $usageStatement->execute();
        $usageCount = (int) $usageStatement->get_result()->fetch_assoc()['total'];
        $usageStatement->close();

        if ($usageCount > 0) {
            $error = 'This meal cannot be deleted because it is assigned to a train or booking.';
        } else {
            try {
                $statement = $conn->prepare('DELETE FROM meals WHERE meal_id = ?');
                $statement->bind_param('i', $meal_id);
                $statement->execute();
                $statement->close();
                $message = 'Meal deleted successfully.';
            } catch (mysqli_sql_exception $exception) {
                $error = 'This meal cannot be deleted because it is still in use.';
            }
        }
    // Shared validation for add/update: name required, price cannot be negative.
    } elseif ($meal_name === '' || $price < 0) {
        $error = 'Meal name is required and price cannot be negative.';
    // Update the existing meal identified by meal_id (duplicate names are caught by the DB).
    } elseif ($action === 'update') {
        try {
            $statement = $conn->prepare('UPDATE meals SET meal_name = ?, price = ? WHERE meal_id = ?');
            $statement->bind_param('sdi', $meal_name, $price, $meal_id);
            $message = $statement->execute() ? 'Meal updated successfully.' : 'Could not update the meal.';
            $statement->close();
        } catch (mysqli_sql_exception $exception) {
            $error = 'That meal name already exists or could not be saved.';
        }
    } else {
        // No action given - insert a new meal.
        try {
            $statement = $conn->prepare('INSERT INTO meals (meal_name, price) VALUES (?, ?)');
            $statement->bind_param('sd', $meal_name, $price);
            $message = $statement->execute() ? 'Meal added successfully.' : 'Could not add the meal.';
            $statement->close();
        } catch (mysqli_sql_exception $exception) {
            $error = 'That meal name already exists or could not be saved.';
        }
    }
}

// ?edit=<id> - load the meal being edited so the form shows its current values.
if (isset($_GET['edit'])) {
    $meal_id = (int) $_GET['edit'];
    $statement = $conn->prepare('SELECT meal_id, meal_name, price FROM meals WHERE meal_id = ?');
    $statement->bind_param('i', $meal_id);
    $statement->execute();
    $editing = $statement->get_result()->fetch_assoc();
}

// All meals for the menu table.
$meals = admin_query($conn, 'SELECT meal_id, meal_name, price FROM meals ORDER BY meal_name');
admin_header('Meals', 'meals');
?>
<!-- Flash messages -->
<?php if ($message !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if ($error !== ''): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- Add / edit meal form (doubles as the edit form when ?edit=<id> is set) -->
<section class="panel" style="margin-bottom: 20px">
    <div class="panel-heading"><h2><?= $editing ? 'Edit meal' : 'Add a meal' ?></h2><?php if ($editing): ?><a class="text-link" href="meals.php">Cancel edit</a><?php endif; ?></div>
    <form method="post">
        <div class="form-grid">
            <div><label for="meal_name">Meal name</label><input id="meal_name" name="meal_name" value="<?= htmlspecialchars($editing['meal_name'] ?? '') ?>" placeholder="e.g. Vegetarian meal" required></div>
            <div><label for="price">Price</label><input id="price" name="price" type="number" min="0" step="0.01" value="<?= htmlspecialchars($editing['price'] ?? '0') ?>" placeholder="0.00" required></div>
        </div>
        <!-- Hidden fields switch this form from "add" to "update" mode. -->
        <?php if ($editing): ?><input type="hidden" name="action" value="update"><input type="hidden" name="meal_id" value="<?= (int) $editing['meal_id'] ?>"><?php endif; ?>
        <div class="form-actions"><button class="button button-primary" type="submit"><?= $editing ? 'Save changes' : 'Add meal' ?></button></div>
    </form>
</section>

<!-- Meal menu table -->
<section class="panel"><div class="panel-heading"><h2>Meal menu</h2><span class="muted"><?= $meals ? $meals->num_rows : 0 ?> records</span></div>
<?php if ($meals && $meals->num_rows > 0): ?><div class="table-wrap"><table class="data-table"><thead><tr><th>Meal</th><th>Price</th><th>Actions</th></tr></thead><tbody>
<?php while ($meal = $meals->fetch_assoc()): ?><tr><td><strong><?= htmlspecialchars($meal['meal_name']) ?></strong></td><td>₹<?= number_format((float) $meal['price'], 2) ?></td><td><div class="action-links"><a href="?edit=<?= (int) $meal['meal_id'] ?>">Edit</a><form method="post" onsubmit="return confirm('Delete this meal?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="meal_id" value="<?= (int) $meal['meal_id'] ?>"><button class="button button-danger" type="submit">Delete</button></form></div></td></tr><?php endwhile; ?>
</tbody></table></div><?php else: ?><div class="empty-state">No meals have been added yet.</div><?php endif; ?></section>
<?php admin_footer(); ?>