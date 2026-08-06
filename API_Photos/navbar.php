<style>
    .main-nav {
        background: linear-gradient(135deg, #1d7a34 0%, #28a745 100%);
        padding: 12px 0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .nav-logo {
        width: 45px;
        height: 45px;
        object-fit: contain;
        background: white;
        padding: 3px;
        border-radius: 10px;
        transition: transform 0.3s ease;
    }
    .navbar-brand:hover .nav-logo {
        transform: scale(1.1) rotate(5deg);
    }
    .brand-text {
        line-height: 1.2;
        margin-left: 12px;
    }
    .status-dot {
        height: 10px;
        width: 10px;
        background-color: #2ecc71;
        border-radius: 50%;
        display: inline-block;
        margin-right: 5px;
        box-shadow: 0 0 8px #2ecc71;
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
    @media (max-width: 991.98px) {
        .navbar-nav {
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: 15px;
        }
        .nav-link {
            padding: 10px 15px !important;
            margin-bottom: 5px;
        }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark main-nav mb-4">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="https://cdn-icons-png.flaticon.com/512/1147/1147560.png" alt="SugarAI Logo" class="nav-logo">
            <div class="brand-text">
                <span class="d-block fw-bold h4 mb-0 text-uppercase">TIS-ใบอ้อย</span>
                <small class="fw-light" style="font-size: 0.7rem; letter-spacing: 1px;">Smart Sugarcane Inspection</small>
            </div>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sugarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="sugarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item me-4">
                    <span class="nav-link text-white small">
                        <span class="status-dot"></span> AI System Online
                    </span>
                </li>
                <li class="nav-item">
                    <a class="btn btn-white btn-sm rounded-pill px-4 fw-bold shadow-sm bg-white text-success" href="index.php">
                        <i class="bi bi-house-door-fill"></i> หน้าแรก
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>