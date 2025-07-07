
<?php
// FILE: zaiko/views/dashboard.php (The View)
// This file contains only the presentation code. All data is provided by the controller.
?>
<style>
  :root {
    --primary-color: #4361ee;
    --secondary-color: #3f37c9;
    --accent-color: #4895ef;
    --success-color: #4cc9f0;
    --warning-color: #f8961e;
    --danger-color: #f94144;
    --light-bg: #f8f9fa;
    --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
  }

  .dashboard-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
    color: white;
    border-radius: 12px 12px 0 0 !important;
    padding: 1.5rem 2rem;
  }

  .stat-card {
    border: none;
    border-radius: 12px;
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    height: 100%;
  }

  .stat-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--card-shadow);
  }

  .stat-card .card-body {
    padding: 1.5rem;
    color: white;
  }

  .stat-card .stat-icon {
    font-size: 2.5rem;
    opacity: 0.2;
    position: absolute;
    right: 20px;
    top: 20px;
  }

  .chart-row {
    display: flex;
    flex-wrap: wrap;
    margin: -0.5rem;
  }

  .chart-col {
    flex: 0 0 calc(50% - 1rem);
    max-width: calc(50% - 1rem);
    margin: 0.5rem;
    min-height: 280px;
  }

  .chart-container {
    height: 220px;
    min-height: 160px;
    max-height: 280px;
    position: relative;
    padding: 1rem;
    background: white;
    border-radius: 12px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  .chart-container canvas {
    display: block;
    max-width: 100%;
    max-height: 100%;
    margin: 0 auto;
  }

  @media (max-width: 992px) {
    .chart-col {
      flex: 0 0 100%;
      max-width: 100%;
    }
  }

  .activity-item {
    border-left: 3px solid transparent;
    transition: all 0.3s ease;
    padding: 1rem 1.25rem;
  }

  .activity-item:hover {
    background-color: rgba(67, 97, 238, 0.05) !important;
    border-left-color: var(--primary-color);
  }

  .quick-link-btn {
    border-radius: 8px;
    padding: 0.75rem;
    font-weight: 500;
    transition: all 0.3s ease;
    text-align: left;
    margin-bottom: 0.75rem;
  }

  .quick-link-btn i {
    margin-right: 10px;
    font-size: 1.1rem;
  }

  .value-card {
    border-radius: 10px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    border-left: 4px solid;
    transition: transform 0.3s ease;
  }

  .value-card:hover {
    transform: translateY(-3px);
  }

  .welcome-message {
    background: linear-gradient(135deg, #f6faff 0%, #e6f0ff 100%);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border-left: 4px solid var(--primary-color);
  }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

<div class="container-fluid px-4">
  <div class="card border-0 shadow-lg overflow-hidden">
    <div class="card-header dashboard-header d-flex justify-content-between align-items-center">
      <div>
        <h2 class="mb-1"><i class="bi bi-boxes me-2"></i>Inventory Dashboard</h2>
        <p class="mb-0 opacity-75">Real-time overview of your inventory system</p>
      </div>
      <div class="badge bg-white text-primary p-2 px-3 rounded-pill">
        <i class="bi bi-calendar-alt me-1"></i> <?= date('F j, Y') ?>
      </div>
    </div>

    <div class="card-body bg-light">
      <div class="welcome-message">
        <div class="d-flex align-items-center">
          <div class="flex-grow-1">
            <h4 class="mb-1 text-primary">Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>!</h4>
            <p class="mb-0 text-muted">Here's what's happening with your inventory today</p>
          </div>
        </div>
      </div>

      <div class="row mb-4 g-4">
        <div class="col-md-4">
          <div class="stat-card" style="background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);">
            <div class="card-body position-relative">
              <i class="bi bi-boxes stat-icon"></i>
              <h5 class="card-title mb-3">Total Items</h5>
              <p class="card-text display-5 fw-bold mb-0"><?= number_format($stats['total_items']) ?></p>
              <small class="opacity-75">Across all categories</small>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="stat-card" style="background: linear-gradient(135deg, #f8961e 0%, #f3722c 100%);">
            <div class="card-body position-relative">
              <i class="bi bi-truck-loading stat-icon"></i>
              <h5 class="card-title mb-3">Pending Deliveries</h5>
              <p class="card-text display-5 fw-bold mb-0"><?= number_format($pending_deliveries) ?></p>
              <small class="opacity-75">Awaiting processing</small>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="stat-card" style="background: linear-gradient(135deg, #f94144 0%, #d90429 100%);">
            <div class="card-body position-relative">
              <i class="bi bi-exclamation-triangle stat-icon"></i>
              <h5 class="card-title mb-3">Low Stock Alerts</h5>
              <p class="card-text display-5 fw-bold mb-0"><?= number_format($stats['low_stock_items']) ?></p>
              <small class="opacity-75">Needs attention</small>
            </div>
          </div>
        </div>
      </div>

      <div class="chart-row">
        <div class="chart-col">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-chart-line text-primary me-2"></i>Inventory Movement</h5>
                <span class="badge bg-primary bg-opacity-10 text-primary">Last 7 Days</span>
              </div>
              <div class="chart-container">
                <canvas id="lineChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <div class="chart-col">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body p-3 d-flex flex-column">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-chart-pie text-warning me-2"></i>Inventory Status</h5>
                <span class="badge bg-warning bg-opacity-10 text-warning">Current</span>
              </div>
              <div class="chart-container">
                <canvas id="pieChart"></canvas>
              </div>
              <div class="mt-3 d-flex justify-content-center gap-2 flex-wrap">
                <span class="badge rounded-pill" style="background: #ff6384">Low Stock</span>
                <span class="badge rounded-pill" style="background: #ffcd56">Pending</span>
                <span class="badge rounded-pill" style="background: #4bc0c0">In Stock</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4 mt-3">
        <div class="col-lg-7">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0"><i class="bi bi-history text-info me-2"></i>Recent Activity</h5>
              </div>
              <div class="list-group list-group-flush">
                <?php foreach ($recent_activity as $activity): ?>
                  <div class="activity-item list-group-item border-0">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                      <div>
                        <span class="fw-bold"><?= htmlspecialchars($activity['user']) ?></span>
                        <span class="<?= $activity['movement_type'] === 'in' ? 'text-success' : 'text-danger' ?>">
                          <?= $activity['movement_type'] === 'in' ? 'added' : 'removed' ?>
                        </span>
                        <span class="fw-bold"><?= $activity['quantity'] ?></span>
                        <span>of</span>
                        <span class="fw-bold"><?= htmlspecialchars($activity['item_name']) ?></span>
                      </div>
                      <small class="text-muted">
                        <?= date('M j, g:i a', strtotime($activity['created_at'])) ?>
                      </small>
                    </div>
                    <div class="d-flex align-items-center">
                      <span class="badge bg-light text-dark me-2">
                        <i class="bi bi-tag me-1"></i>
                        <?= ucfirst($activity['reference_type']) ?> #<?= $activity['reference_id'] ?>
                      </span>
                      <?php if (!empty($activity['notes'])): ?>
                        <small class="text-muted"><i class="bi bi-chat-left-text me-1"></i><?= htmlspecialchars($activity['notes']) ?></small>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <h5><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Quick Actions</h5>
              <div class="mb-4">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                  <a href="items.php?action=add" class="btn quick-link-btn btn-outline-primary d-block">
                    <i class="bi bi-plus-circle"></i> Add New Item
                  </a>
                <?php endif; ?>
                <a href="delivery_receipt.php" class="btn quick-link-btn btn-outline-success d-block">
                  <i class="bi bi-truck"></i> Create Delivery Receipt
                </a>
              </div>

              <h5 class="mt-4"><i class="bi bi-cash-coin text-success me-2"></i>Inventory Value</h5>
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="value-card bg-light" style="border-left-color: #4361ee;">
                    <div class="d-flex align-items-center">
                      <div>
                        <small class="text-muted d-block">Non-Taxed Value</small>
                        <h4 class="mb-0 fw-bold">₱<?= number_format($inventory_value['total_nontaxed_value'] ?? 0, 2) ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="value-card bg-light" style="border-left-color: #f8961e;">
                    <div class="d-flex align-items-center">
                      <div>
                        <small class="text-muted d-block">Taxed Value</small>
                        <h4 class="mb-0 fw-bold">₱<?= number_format($inventory_value['total_taxed_value'] ?? 0, 2) ?></h4>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // --- Line Chart ---
    if (document.getElementById('lineChart')) {
      new Chart(document.getElementById('lineChart'), {
        type: 'line',
        data: {
          labels: <?= json_encode($movement_dates) ?>,
          datasets: [{
            label: 'Items Added',
            data: <?= json_encode($items_added_series) ?>,
            borderColor: '#4361ee',
            backgroundColor: 'rgba(67, 97, 238, 0.1)',
            fill: true,
            tension: 0.3
          }, {
            label: 'Items Removed',
            data: <?= json_encode(array_map('abs', $items_removed_series)) ?>,
            borderColor: '#f94144',
            backgroundColor: 'rgba(249, 65, 68, 0.1)',
            fill: true,
            tension: 0.3
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
        }
      });
    }

    // --- Pie Chart ---
    if (document.getElementById('pieChart')) {
      const pieData = {
        labels: ['Low Stock', 'Pending Deliveries', 'In Stock'],
        datasets: [{
          data: [
            <?= (int)($stats['low_stock_items'] ?? 0) ?>,
            <?= (int)($pending_deliveries ?? 0) ?>,
            <?= max(0, ($stats['total_items'] ?? 0) - ($stats['low_stock_items'] ?? 0) - ($pending_deliveries ?? 0)) ?>
          ],
          backgroundColor: ['#ff6384', '#ffcd56', '#4bc0c0'],
          borderWidth: 0
        }]
      };
      new Chart(document.getElementById('pieChart'), {
        type: 'doughnut',
        data: pieData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '70%',
          plugins: {
            legend: { display: false },
            datalabels: {
              color: '#fff',
              font: { weight: 'bold' },
              formatter: (value) => {
                const total = pieData.datasets[0].data.reduce((a, b) => a + b, 0);
                return total ? Math.round((value / total) * 100) + '%' : '0%';
              }
            }
          }
        },
        plugins: [ChartDataLabels]
      });
    }
  });
</script>
```