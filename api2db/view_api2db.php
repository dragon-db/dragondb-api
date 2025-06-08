<?php
// Import configuration
require_once 'config.php';

// Create connection using config variables
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch data from the table with ordering by latest first
$sql = "SELECT app_id, app_usage, app_res_url, app_res_header, app_req_method, app_res_body, app_res_time FROM app_to_db ORDER BY app_id DESC";
$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API2DB Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            font-weight: 300;
        }

        .header p {
            opacity: 0.8;
            font-size: 1.1rem;
        }

        .stats {
            display: flex;
            justify-content: space-around;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        .table-container {
            overflow-x: auto;
            padding: 0;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            border-radius: 10px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            margin: 20px;
        }

        /* Custom scrollbar for better visibility */
        .table-container::-webkit-scrollbar {
            width: 12px;
            height: 12px;
        }

        .table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .table-container::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #3498db, #2980b9);
            border-radius: 10px;
            border: 2px solid #f1f1f1;
        }

        .table-container::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #2980b9, #1f4e79);
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 0;
            overflow: hidden;
            box-shadow: none;
            min-width: 1200px;
        }

        .data-table th {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
            z-index: 10;
            border: none;
        }

        .data-table td {
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: top;
        }

        .data-table tr:hover {
            background-color: #f8f9fa;
            transition: background-color 0.3s ease;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .method-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
            min-width: 60px;
            text-align: center;
        }

        .method-get { background: #d4edda; color: #155724; }
        .method-post { background: #d1ecf1; color: #0c5460; }
        .method-put { background: #fff3cd; color: #856404; }
        .method-delete { background: #f8d7da; color: #721c24; }

        .json-content {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px 15px 15px 45px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 0.85rem;
            line-height: 1.4;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
            position: relative;
            min-height: 80px;
            transition: all 0.3s ease;
        }

        .json-content:hover {
            border-color: #3498db;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.2);
        }

        /* Enhanced scrollbar for JSON content */
        .json-content::-webkit-scrollbar {
            width: 8px;
        }

        .json-content::-webkit-scrollbar-track {
            background: #e9ecef;
            border-radius: 4px;
        }

        .json-content::-webkit-scrollbar-thumb {
            background: #3498db;
            border-radius: 4px;
        }

        .json-content::-webkit-scrollbar-thumb:hover {
            background: #2980b9;
        }

        /* Scroll indicator for JSON content */
        .json-content::after {
            content: "↕ Scroll to view more";
            position: absolute;
            bottom: 5px;
            right: 10px;
            background: rgba(52, 152, 219, 0.8);
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }

        .json-content:hover::after {
            opacity: 1;
        }

        .expandable-content {
            position: relative;
        }

        .expand-btn {
            position: absolute;
            top: 5px;
            left: 5px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 0.7rem;
            cursor: pointer;
            z-index: 5;
            transition: all 0.3s ease;
        }

        .expand-btn:hover {
            background: #2980b9;
            transform: scale(1.05);
        }

        .url-cell {
            max-width: 250px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 0.85rem;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }

        .id-cell {
            font-weight: bold;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            text-align: center;
            border-radius: 5px;
            min-width: 60px;
        }

        .time-cell {
            font-size: 0.85rem;
            color: #6c757d;
            white-space: nowrap;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
        }

        .service-cell {
            font-weight: 600;
            color: #2c3e50;
            background: #e8f4f8;
            padding: 10px;
            border-radius: 5px;
        }

        .no-data {
            text-align: center;
            padding: 50px;
            color: #6c757d;
            font-size: 1.1rem;
        }

        .no-data i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        /* Column width optimization */
        .data-table th:nth-child(1), .data-table td:nth-child(1) { width: 80px; }
        .data-table th:nth-child(2), .data-table td:nth-child(2) { width: 180px; }
        .data-table th:nth-child(3), .data-table td:nth-child(3) { width: 100px; }
        .data-table th:nth-child(4), .data-table td:nth-child(4) { width: 400px; }
        .data-table th:nth-child(5), .data-table td:nth-child(5) { width: 300px; }
        .data-table th:nth-child(6), .data-table td:nth-child(6) { width: 150px; }
        .data-table th:nth-child(7), .data-table td:nth-child(7) { width: 250px; }

        /* Modal for expanded view */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }

        .close-btn {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-btn:hover {
            background: #c0392b;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .stats {
                flex-direction: column;
                gap: 10px;
            }

            .stat-item {
                padding: 10px;
            }

            .table-container {
                margin: 10px;
                max-height: 70vh;
            }

            .data-table {
                font-size: 0.85rem;
                min-width: 800px;
            }

            .data-table th,
            .data-table td {
                padding: 12px 8px;
            }

            .json-content {
                font-size: 0.75rem;
                max-height: 200px;
                padding: 10px;
            }

            .url-cell {
                max-width: 200px;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 20px 15px;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .data-table {
                font-size: 0.8rem;
            }

            .data-table th,
            .data-table td {
                padding: 10px 6px;
            }

            .json-content {
                max-height: 150px;
                padding: 8px;
            }
        }

        /* Scroll to top button */
        .scroll-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            cursor: pointer;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .scroll-top.visible {
            opacity: 1;
            visibility: visible;
        }

        .scroll-top:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.4);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 API2DB Dashboard</h1>
            <p>Monitor and analyze your API requests and responses</p>
        </div>

        <div class="stats">
            <div class="stat-item">
                <div class="stat-number"><?php echo $result->num_rows; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $app_usage; ?></div>
                <div class="stat-label">Service</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo date('d M Y'); ?></div>
                <div class="stat-label">Last Updated</div>
            </div>
        </div>

        <div class="table-container" id="tableContainer">
            <?php if ($result->num_rows > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Timestamp</th>
                            <th>Method</th>
                            <th>Request Body</th>
                            <th>Headers</th>
                            <th>Service</th>
                            <th>URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="id-cell">#<?php echo htmlspecialchars($row['app_id']); ?></td>
                                <td class="time-cell"><?php echo htmlspecialchars($row['app_res_time']); ?></td>
                                <td>
                                    <span class="method-badge method-<?php echo strtolower($row['app_req_method']); ?>">
                                        <?php echo htmlspecialchars($row['app_req_method']); ?>
                                    </span>
                                </td>
                                <td class="expandable-content">
                                    <button class="expand-btn" onclick="showModal('body-<?php echo $row['app_id']; ?>')">🔍</button>
                                    <div class="json-content" id="body-<?php echo $row['app_id']; ?>">
                                        <?php 
                                        if (!empty($row['app_res_body'])) {
                                            $body = json_decode($row['app_res_body'], true);
                                            if ($body) {
                                                echo htmlspecialchars(json_encode($body, JSON_PRETTY_PRINT));
                                            } else {
                                                echo htmlspecialchars($row['app_res_body']);
                                            }
                                        } else {
                                            echo '<em style="color: #6c757d;">No body content</em>';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="expandable-content">
                                    <button class="expand-btn" onclick="showModal('headers-<?php echo $row['app_id']; ?>')">🔍</button>
                                    <div class="json-content" id="headers-<?php echo $row['app_id']; ?>">
                                        <?php 
                                        $headers = json_decode($row['app_res_header'], true);
                                        if ($headers) {
                                            echo htmlspecialchars(json_encode($headers, JSON_PRETTY_PRINT));
                                        } else {
                                            echo htmlspecialchars($row['app_res_header']);
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="service-cell"><?php echo htmlspecialchars($row['app_usage']); ?></td>
                                <td class="url-cell"><?php echo htmlspecialchars($row['app_res_url']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <div style="font-size: 3rem; margin-bottom: 20px;">📊</div>
                    <h3>No API requests found</h3>
                    <p>Start making API calls to see data here</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal for expanded content -->
    <div id="contentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Content Details</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div class="json-content" id="modalContent" style="max-height: 60vh;"></div>
        </div>
    </div>

    <!-- Scroll to top button -->
    <button class="scroll-top" id="scrollTopBtn" onclick="scrollToTop()">↑</button>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);

        // Show/hide scroll to top button
        const scrollTopBtn = document.getElementById('scrollTopBtn');
        const tableContainer = document.getElementById('tableContainer');

        tableContainer.addEventListener('scroll', function() {
            if (tableContainer.scrollTop > 300) {
                scrollTopBtn.classList.add('visible');
            } else {
                scrollTopBtn.classList.remove('visible');
            }
        });

        // Scroll to top function
        function scrollToTop() {
            tableContainer.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Modal functions
        function showModal(elementId) {
            const element = document.getElementById(elementId);
            const modal = document.getElementById('contentModal');
            const modalContent = document.getElementById('modalContent');
            const modalTitle = document.getElementById('modalTitle');
            
            modalContent.innerHTML = element.innerHTML;
            modalTitle.textContent = elementId.includes('headers') ? 'Request Headers' : 'Request Body';
            modal.style.display = 'block';
            
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('contentModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('contentModal');
            if (event.target === modal) {
                closeModal();
            }
        }

        // ESC key to close modal
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>

<?php
// Close connection
$conn->close();
?>