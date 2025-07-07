<?php include_once '../header.php'; ?>

<div class="container mt-5">
    <h2 class="text-center text-primary mb-4">Activity Log Per Dog</h2>

    <!-- Activity logs for Buddy -->
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title">Buddy (Golden Retriever)</h4>
            <ul>
                <li><strong>07/06/2025</strong> – Emily R. walked Buddy for 30 mins</li>
                <li><strong>07/05/2025</strong> – Emily R. fed Buddy dinner</li>
                <li><strong>07/04/2025</strong> – Emily R. gave Buddy his meds</li>
            </ul>
        </div>
    </div>

    <!-- Activity logs for Luna -->
    <div class="card mb-4">
        <div class="card-body">
            <h4 class="card-title">Luna (Husky)</h4>
            <ul>
                <li><strong>07/06/2025</strong> – Jake T. did training session with Luna</li>
                <li><strong>07/05/2025</strong> – Jake T. fed Luna breakfast</li>
            </ul>
        </div>
    </div>

    <p class="text-muted text-center">* This data will be loaded dynamically from MQ/database later.</p>
</div>

<?php include_once '../footer.php'; ?>