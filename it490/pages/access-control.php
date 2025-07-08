<?php include_once '../header.php'; ?>

<div class="container mt-5">
    <h2 class="text-center text-success mb-4">Manage Sitter Access to Your Dogs</h2>

    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Buddy (Golden Retriever)</h5>
            <form>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="sitters[]" value="1" id="sitter1" checked>
                    <label class="form-check-label" for="sitter1">
                        Emily R.
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="sitters[]" value="2" id="sitter2">
                    <label class="form-check-label" for="sitter2">
                        Jake T.
                    </label>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Update Access</button>
            </form>
        </div>
    </div>

    <!-- Repeat for other dogs -->
</div>

<?php include_once '../footer.php'; ?>