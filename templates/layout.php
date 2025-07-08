<?php 
// zaiko/templates/layout.php
if (session_status() == PHP_SESSION_NONE) session_start();
$user_role = $_SESSION['role'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Inventory Management System">

  <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" as="style" crossorigin="anonymous">
  <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" as="style">

  <title><?= htmlspecialchars($page_title ?? 'Inventory System') ?></title>
  <link rel="icon" href="<?= $base_url ?? '' ?>assets/favicon.ico" type="image/x-icon">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="<?= $base_url ?? '' ?>assets/styles.css?v=<?= filemtime('assets/styles.css') ?>">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

  <style>
    :root {
      --primary-color: #0d6efd;
      --navbar-bg: linear-gradient(135deg, #1a1e23 0%, #2a2f37 100%);
      --navbar-active: rgba(13, 110, 253, 0.2);
      --navbar-hover: rgba(255, 255, 255, 0.05);
      --navbar-border: rgba(255, 255, 255, 0.1);
      --navbar-text: rgba(255, 255, 255, 0.9);
      --navbar-icon: #6c757d;
      --navbar-icon-active: #0d6efd;
      --navbar-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      --navbar-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
      --navbar-highlight: rgba(13, 110, 253, 0.1);
    }

    html, body {
      height: 100%;
      margin: 0;
      font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
      background-color: #f8f9fa;
    }

    .navbar-main {
      background: var(--navbar-bg);
      box-shadow: var(--navbar-shadow);
      border-bottom: 1px solid var(--navbar-border);
      position: sticky;
      top: 0;
      z-index: 1030;
      padding: 0.5rem 1rem;
    }

    .navbar-brand img {
      height: 50px;
      transition: all 0.3s ease;
      filter: drop-shadow(0 0 8px rgba(13, 110, 253, 0.4));
    }

    .navbar-brand img:hover {
      transform: scale(1.05);
      filter: drop-shadow(0 0 12px rgba(13, 110, 253, 0.6));
    }

    .nav-link {
      transition: var(--navbar-transition);
      border-radius: 6px;
      margin: 2px 4px;
      padding: 8px 12px;
      color: var(--navbar-text);
      font-weight: 400;
      display: flex;
      align-items: center;
      position: relative;
    }

    .nav-link::before {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 0;
      height: 2px;
      background: var(--primary-color);
      transition: var(--navbar-transition);
    }

    .nav-link:hover,
    .nav-link:focus {
      background: var(--navbar-hover);
      color: white;
    }

    .nav-link:hover::before,
    .nav-link:focus::before {
      width: 80%;
    }

    .nav-link.active {
      background: var(--navbar-active);
      color: white;
      font-weight: 500;
    }

    .nav-link.active::before {
      width: 80%;
      background: white;
    }

    .nav-link i {
      color: var(--navbar-icon);
      margin-right: 8px;
      font-size: 1rem;
      transition: var(--navbar-transition);
    }

    .nav-link:hover i,
    .nav-link:focus i,
    .nav-link.active i {
      color: var(--navbar-icon-active);
    }

    .dropdown-menu {
      background: #2a2f37;
      border: 1px solid var(--navbar-border);
    }

    .dropdown-item {
      color: var(--navbar-text);
    }

    .dropdown-item:hover,
    .dropdown-item:focus {
      background: var(--navbar-hover);
      color: white;
    }

    .dropdown-divider {
      border-color: var(--navbar-border);
    }

    .user-panel {
      display: flex;
      align-items: center;
      padding: 0.5rem;
      background: rgba(0, 0, 0, 0.2);
      border-radius: 8px;
      border: 1px solid var(--navbar-border);
      backdrop-filter: blur(5px);
    }

    .user-info {
      margin-left: 10px;
    }

    .username {
      font-weight: 500;
      color: white;
      margin: 0;
      font-size: 0.95rem;
    }

    .user-role {
      color: rgba(255, 255, 255, 0.7);
      font-size: 0.75rem;
      display: block;
    }

    .badge-role {
      font-size: 0.7rem;
      padding: 3px 6px;
      font-weight: 500;
      letter-spacing: 0.5px;
      background-color: var(--primary-color);
    }

    .logout-btn {
      background: rgba(220, 53, 69, 0.7);
      border: none;
      transition: var(--navbar-transition);
      margin-left: 10px;
    }

    .logout-btn:hover {
      background: rgba(220, 53, 69, 0.9);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }

    .logout-btn i {
      color: white !important;
    }

    .content {
      padding-top: 20px;
      min-height: calc(100vh - 70px);
      background: #f8f9fa;
    }

    /* Mobile menu toggle button */
    .navbar-toggler {
      border: none;
      padding: 0.5rem;
    }

    .navbar-toggler:focus {
      box-shadow: none;
    }

    .navbar-toggler-icon {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.9%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    /* Animation for dropdown menus */
    @keyframes slideDown {
      0% {
        opacity: 0;
        transform: translateY(-10px);
      }
      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .dropdown-menu {
      animation: slideDown 0.3s ease forwards;
    }

    /* Responsive adjustments */
    @media (max-width: 992px) {
      .navbar-collapse {
        position: fixed;
        top: 70px;
        left: 0;
        right: 0;
        background: var(--navbar-bg);
        padding: 1rem;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        max-height: calc(100vh - 70px);
        overflow-y: auto;
      }
      
      .nav-link {
        margin: 4px 0;
        padding: 10px 15px;
      }
      
      .user-panel {
        margin-top: 10px;
        padding: 10px;
      }
    }
  </style>
</head>

<body>
  <!-- Top Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-dark navbar-main">
    <div class="container-fluid">
      <a class="navbar-brand" href="dashboard.php">
        <img src="<?= $base_url ?? '' ?>assets/terralogix1.png" alt="Inventory System Logo">
      </a>
      
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
        <span class="navbar-toggler-icon"></span>
      </button>
      
      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav me-auto">
          <!-- Dashboard -->
          <li class="nav-item">
            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
              <i class="bi bi-speedometer2"></i>
              <span>Dashboard</span>
            </a>
          </li>
          
          <!-- Inventory Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="inventoryDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-box-seam"></i>
              <span>Inventory</span>
            </a>
            <ul class="dropdown-menu" aria-labelledby="inventoryDropdown">
              <li><a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) === 'items.php' ? 'active' : '' ?>" href="items.php">
                <i class="bi bi-box"></i>
                <span>Items</span>
              </a></li>
              <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager'])): ?>
                <li><a class="dropdown-item" href="#" id="openAddItemModal">
                  <i class="bi bi-plus-circle"></i>
                  <span>Add Item</span>
                </a></li>
                <li><a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) === 'old_stocks.php' ? 'active' : '' ?>" href="old_stocks.php">
                  <i class="bi bi-archive"></i>
                  <span>Old Stocks</span>
                </a></li>
              <?php endif; ?>
            </ul>
          </li>
          
          <!-- Transactions Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="transactionsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-truck"></i>
              <span>Transactions</span>
            </a>
            <ul class="dropdown-menu" aria-labelledby="transactionsDropdown">
              <li><a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) === 'delivery_receipt.php' ? 'active' : '' ?>" href="delivery_receipt.php">
                <i class="bi bi-receipt"></i>
                <span>Delivery Receipt</span>
              </a></li>
              <li><a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) === 'delivery_history.php' ? 'active' : '' ?>" href="delivery_history.php">
                <i class="bi bi-clock-history"></i>
                <span>Delivery History</span>
              </a></li>
            </ul>
          </li>
          
          <!-- Requests Dropdown -->
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="requestsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-clipboard2-pulse"></i>
              <span>Requests</span>
            </a>
            <ul class="dropdown-menu" aria-labelledby="requestsDropdown">
              <li><a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) === 'materials_request.php' ? 'active' : '' ?>" href="materials_request.php">
                <i class="bi bi-clipboard2"></i>
                <span>Materials Request</span>
              </a></li>
              <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager'])): ?>
                <li><a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) === 'materials_request_admin.php' ? 'active' : '' ?>" href="materials_request_admin.php">
                  <i class="bi bi-check-circle"></i>
                  <span>Approve Requests</span>
                </a></li>
                <li><a class="dropdown-item <?= basename($_SERVER['PHP_SELF']) === 'purchase_orders.php' ? 'active' : '' ?>" href="purchase_orders.php">
                  <i class="bi bi-clipboard-check"></i>
                  <span>Purchase Orders</span>
                </a></li>
              <?php endif; ?>
            </ul>
          </li>
          
          <!-- Admin Only Links -->
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
              <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>" href="users.php">
                <i class="bi bi-people"></i>
                <span>Manage Users</span>
              </a>
            </li>
          <?php endif; ?>
          
          <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager'])): ?>
            <li class="nav-item">
              <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'returned_items_page.php' ? 'active' : '' ?>" href="returned_items_page.php">
                <i class="bi bi-arrow-return-left"></i>
                <span>Returned Items</span>
              </a>
            </li>
          <?php endif; ?>
          
          <?php if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'manager'])): ?>
            <li class="nav-item">
              <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'suppliers.php' ? 'active' : '' ?>" href="suppliers.php">
                <i class="bi bi-truck"></i>
                <span>Suppliers</span>
              </a>
            </li>
          <?php endif; ?>
          
          <?php if (in_array($user_role, ['admin', 'manager'])): ?>
            <li class="nav-item">
              <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'tools.php' ? 'active' : '' ?>" href="tools.php">
                <i class="bi bi-wrench-adjustable"></i>
                <span>Tools</span>
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'master_items.php' ? 'active' : '' ?>" href="master_items.php">
                <i class="bi bi-box-seam"></i>
                <span>Master Items</span>
              </a>
            </li>
          <?php endif; ?>
        </ul>
        
        <!-- User Panel -->
        <div class="d-flex align-items-center">
          <div class="user-panel">
            <div class="user-info">
              <span class="username"><?= htmlspecialchars($_SESSION['username'] ?? 'Guest') ?></span>
              <span class="user-role">
                <span class="badge badge-role animate__animated animate__pulse animate__infinite"><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? 'none')) ?></span>
              </span>
            </div>
          </div>
          <a class="nav-link logout-btn" href="logout.php" title="Logout">
            <i class="bi bi-box-arrow-right"></i>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="content container-fluid">
    <?php
    if (isset($_SESSION['success'])) {
      echo '<div class="alert alert-success alert-dismissible fade show" role="alert">'
        . htmlspecialchars($_SESSION['success']) .
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
      unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
      echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
        . htmlspecialchars($_SESSION['error']) .
        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
      unset($_SESSION['error']);
    }
    if (!empty($view) && file_exists($view)) {
      if (isset($form_data) && is_array($form_data)) extract($form_data);
      include $view;
    } else {
      echo '<div class="alert alert-danger">Page content not found or view variable not set correctly. Missing view: ' . htmlspecialchars($view ?? '[view variable not set]') . '</div>';
      error_log("Layout Error: View file issue. Path: " . ($view ?? '[view variable not set]'));
    }
    ?>
  </main>

  <!-- Add Item Modal -->
  <div class="modal fade" id="addItemModal" tabindex="-1" aria-labelledby="addItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <form class="modal-content needs-validation" id="addItemForm" action="add_master_item.php" method="POST" autocomplete="off" novalidate>
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title" id="addItemModalLabel">Add New Item</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="item_name" class="form-label">Name</label>
              <input type="text" class="form-control" name="name" id="item_name" required>
            </div>
            <div class="col-md-6">
              <label for="item_sku" class="form-label">Sku.</label>
              <input type="text" class="form-control" name="sku" id="item_sku">
            </div>
          </div>
          <div class="col-md-6">
            <label for="item_serial" class="form-label">Serial Number</label>
            <input type="text" class="form-control" name="serial_number" id="item_serial">
          </div>
          <div class="mb-3 mt-3">
            <label for="item_description" class="form-label">Description</label>
            <textarea class="form-control" name="description" id="item_description" rows="2"></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-4">
              <label for="item_unit" class="form-label">Unit</label>
              <select class="form-select" name="unit" id="item_unit" required>
                <option value="" disabled selected>Select unit</option>
                <option value="pcs">Pieces</option>
                <option value="pc">Piece</option>
                <option value="pr">Pair</option>
                <option value="assy">Assembly</option>
                <option value="unt">Unit</option>
                <option value="set">Set</option>
                <option value="mtrs">Meters</option>
                <option value="ft">Feet</option>
                <option value="box">Box</option>
                <option value="pck">Pack</option>
                <option value="roll">Roll</option>
                <option value="kg">Kilogram</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="item_qty" class="form-label">Qty</label>
              <input type="number" class="form-control" name="quantity" id="item_qty" min="0" step="any" value="1" required>
            </div>
          </div>
          <div class="col-md-4">
            <label for="item_category" class="form-label">Category</label>
            <select class="form-select" name="category" id="item_category" required>
              <option value="Structured Cabling">Structured Cabling</option>
              <option value="FDAS">FDAS</option>
              <option value="Network Switches">Network Switches</option>
              <option value="Router/Firewall">Router/Firewall</option>
              <option value="WAP">WAP</option>
              <option value="Camera/CCTV Equipments">Camera/Surveillance Equipment</option>
              <option value="Electrical">Electrical</option>
              <option value="Telephone">Telephone</option>
              <option value="PVC Pipe">PVC Pipe</option>
              <option value="EMT Pipe">EMT Pipe</option>
              <option value="PVC Fittings">PVC Fittings</option>
              <option value="Power Tools">Power Tools</option>
              <option value="Personal Protective Equipment (PPE)">Personal Protective Equipment (PPE)</option>
              <option value="Access Control">Access Control</option>
              <option value="Tools">Tools</option>
              <option value="Consumables">Consumables</option>
              <option value="Copper Cables">Copper Cables</option>
              <option value="Computer Set">Computer Set</option>
              <option value="Printer">Printer</option>
              <option value="Kiosk">Kiosk</option>
              <option value="Data Cabinet and Trays">Data Cabinet and Trays</option>
            </select>
            <input type="text" class="form-control mt-2 d-none" id="item_category_other" name="category_other" placeholder="Specify other category">
          </div>

          <hr>
          <div class="row g-3 align-items-end">
            <div class="col-md-4">
              <label for="addUnitPrice" class="form-label">Unit Price (₱)</label>
              <div class="input-group">
                <span class="input-group-text">₱</span>
                <input type="number" class="form-control" name="unit_price" id="addUnitPrice" step="0.01" min="0.01" required>
              </div>
              <div class="invalid-feedback">Please provide a valid unit price.</div>
            </div>
            <div class="col-md-4">
              <label for="addMinStockLevel" class="form-label">Min. Stock Level</label>
              <input type="number" class="form-control" name="min_stock_level" id="addMinStockLevel" min="0" value="0" required>
              <div class="invalid-feedback">Please provide a valid minimum stock level.</div>
            </div>
            <div class="col-md-4">
              <label class="form-label">Taxable</label>
              <div class="form-check form-switch mt-1">
                <input class="form-check-input" type="checkbox" name="taxable" id="addTaxableSwitch">
                <label class="form-check-label" for="addTaxableSwitch">Apply 12% Tax</label>
              </div>
            </div>
          </div>
          <div class="row g-3 mt-3">
            <div class="col-md-4">
              <label class="form-label">VAT ex</label>
              <input class="form-control" type="text" id="addPriceNonTaxedDisplay" readonly value="0.00">
            </div>
            <div class="col-md-4">
              <label class="form-label">Taxed Unit Price</label>
              <input class="form-control" type="text" id="addPriceTaxedDisplay" readonly value="0.00">
            </div>
            <div class="col-md-4">
              <label class="form-label">Total Value</label>
              <input class="form-control" type="text" id="addTotalPriceDisplay" readonly value="0.00">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Add Item</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- JS Libraries -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
  
  <script>
    $(document).ready(function() {
      // Initialize DataTables if needed
      if ($('#masterItemsTable').length && !$.fn.DataTable.isDataTable('#masterItemsTable')) {
        var table = $('#masterItemsTable').DataTable({
          scrollX: true,
          pageLength: 10,
          columnDefs: [{ 
            orderable: false, 
            targets: -1 
          }],
          fixedColumns: {
            right: 1
          }
        });
      }

      // FIX: Proper dropdown menu handling
      $('.dropdown').on('hide.bs.dropdown', function() {
        $(this).data('bs.dropdown')._popper?.destroy();
      });

      // Close all dropdowns when opening a new one
      $('.dropdown-toggle').on('click', function(e) {
        var $this = $(this);
        var $parent = $this.closest('.dropdown');
        var wasOpen = $parent.hasClass('show');
        
        // Close all other dropdowns
        $('.dropdown').not($parent).removeClass('show');
        $('.dropdown-menu').not($parent.find('.dropdown-menu')).removeClass('show');
        
        // Toggle current dropdown if it wasn't already open
        if (!wasOpen) {
          $parent.toggleClass('show');
          $parent.find('.dropdown-menu').toggleClass('show');
        }
        
        // Update aria-expanded attribute
        $this.attr('aria-expanded', $parent.hasClass('show'));
      });

      // Prevent Enter key from submitting the form (except for textarea)
      $('#addItemForm input').on('keydown', function(e) {
        if (e.key === 'Enter' && this.type !== 'textarea') {
          e.preventDefault();
        }
      });

      // After barcode is scanned, move focus to quantity input
      $('#item_serial').on('change', function() {
        $('#item_qty').focus();
      });

      // Form submission via AJAX
      $('#addItemForm').on('submit', function(e) {
        e.preventDefault();

        const form = $(this);
        const formData = form.serialize();
        const submitBtn = form.find('button[type="submit"]');

        submitBtn.prop('disabled', true);

        $.post('add_master_item.php', formData, function(response) {
          if (response.trim() === 'success') {
            $('#addItemModal').modal('hide');
            $('main.content').prepend(
              '<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                'Item added successfully!' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
              '</div>'
            );
          } else {
            $('main.content').prepend(
              '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                'Error: ' + response + 
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
              '</div>'
            );
          }
        }).fail(function(xhr, status, error) {
          $('main.content').prepend(
            '<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
              'Error: ' + xhr.status + ' - ' + xhr.statusText +
              '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>'
          );
        }).always(function() {
          submitBtn.prop('disabled', false);
        });
      });

      // Initialize DataTables for returned items if needed
      if (!$.fn.DataTable.isDataTable('#returnedItemsTable')) {
        $('#returnedItemsTable').DataTable({
          responsive: true,
          order: [
            [1, 'desc']
          ]
        });
      }

      // Show modal when "Add Item" is clicked
      $('#openAddItemModal').on('click', function(e) {
        e.preventDefault();
        $('#addItemModal').modal('show');
      });

      // Form validation for Bootstrap 5
      var forms = document.querySelectorAll('.needs-validation');
      Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
          if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add('was-validated');
        }, false);
      });

      // Calculation logic for Add Item Modal
      function recalcAddItemPrices() {
        let inputPrice = parseFloat($('#addUnitPrice').val()) || 0;
        let qty = parseFloat($('#item_qty').val()) || 0;
        let taxable = $('#addTaxableSwitch').is(':checked');

        let price_nontaxed, price_taxed;

        if (taxable) {
          price_nontaxed = inputPrice;
          price_taxed = +(inputPrice * 1.12).toFixed(2);
        } else {
          price_taxed = inputPrice;
          price_nontaxed = +(inputPrice / 1.12).toFixed(2);
        }

        let totalTaxed = +(price_taxed * qty).toFixed(2);

        $('#addPriceNonTaxedDisplay').val(price_nontaxed.toFixed(2));
        $('#addPriceTaxedDisplay').val(price_taxed.toFixed(2));
        $('#addTotalPriceDisplay').val(totalTaxed.toFixed(2));
      }

      // Update calculation on any relevant input change
      $('#addUnitPrice, #item_qty').on('input', recalcAddItemPrices);
      $('#addTaxableSwitch').on('change', recalcAddItemPrices);

      // Optionally recalc on modal show
      $('#addItemModal').on('shown.bs.modal', recalcAddItemPrices);

      // Auto-fill item snapshot fields on item select (for the hidden fields)
      $('#addItemId').on('change', function() {
        const opt = $(this).find('option:selected');
        $('#addNameAtReturn').val(opt.data('name'));
        $('#addSkuAtReturn').val(opt.data('sku'));
        $('#addDescriptionAtReturn').val(opt.data('description'));
        $('#addUnitAtReturn').val(opt.data('unit'));
        $('#addUnitPriceAtReturn').val(opt.data('unit_price'));
        $('#addPriceTaxedAtReturn').val(opt.data('price_taxed'));
        $('#addPriceNontaxedAtReturn').val(opt.data('price_nontaxed'));
      });

      // Edit Return Modal logic (populate modal with AJAX)
      $('.edit-return-btn').on('click', function() {
        const returnId = $(this).data('id');
        $('#editReturnForm').trigger("reset").removeClass('was-validated');

        $.ajax({
          url: 'return_item_actions.php',
          type: 'GET',
          data: {
            action: 'get_return_details',
            return_id: returnId
          },
          dataType: 'json',
          success: function(response) {
            if (response.success && response.data) {
              const record = response.data;
              $('#editReturnId').val(record.id);
              $('#editOriginalItemId').val(record.item_id);

              $('#editItemInfo').html(
                '<strong>Name:</strong> ' + htmlspecialchars(record.name_at_return) + '<br>' +
                '<strong>SKU:</strong> ' + htmlspecialchars(record.sku_at_return || 'N/A') + '<br>' +
                '<strong>Unit:</strong> ' + htmlspecialchars(record.unit_at_return || 'N/A')
              );

              $('#editQuantityReturned').val(record.quantity_returned);
              $('#editOriginalQuantityReturned').val(record.quantity_returned);
              $('#editReason').val(record.reason || '');

              let returnDateISO = record.return_date.replace(' ', 'T').substring(0, 16);
              $('#editReturnDate').val(returnDateISO);

              $('#editReturnedBy').val(record.returned_by || '');
              $('#editReceivedBy').val(record.received_by || '');
            } else {
              alert('Error fetching return details: ' + (response.message || 'Unknown error'));
            }
          },
          error: function(xhr, status, error) {
            alert('AJAX Error fetching return details: ' + error);
          }
        });
      });

      // Utility to escape HTML
      function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }
    });
  </script>
</body>
</html>
<?php ob_end_flush(); ?>