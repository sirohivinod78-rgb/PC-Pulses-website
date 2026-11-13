<?php
/**
 * Template Name: PC Builder
 * Template Post Type: page
 * 
 * PC Builder Page Template
 */

get_header();
?>

<div id="content" class="site-content">
    <main class="pc-builder-main">
        <div class="pc-builder-header">
            <h1>Custom PC Builder</h1>
            <div class="pc-builder-breadcrumb" aria-label="Breadcrumb">
                <span>Home</span>
                <span class="pc-builder-breadcrumb-separator" aria-hidden="true">&gt;</span>
                <span>PC Builder</span>
            </div>
        </div>

        <div class="pc-builder-container">
            <!-- Main Component Selection Area -->
            <div class="pc-builder-left">
                <div id="compatibility-warnings"></div>
                <div id="pc-builder-components" class="pc-builder-components"></div>
            </div>

            <!-- Build Summary Sidebar -->
            <aside id="pc-builder-summary">
                <div class="summary-header">Build Summary</div>

                <!-- Progress Indicator -->
                <div class="progress-section">
                    <div class="progress-text">Build Completion</div>
                    <div class="progress-bar">
                        <div class="progress-bar-fill"></div>
                    </div>
                    <div class="progress-value" style="text-align: center; font-weight: bold; color: #272B36;"></div>
                </div>

                <!-- Selected Components -->
                <div id="build-summary-items"></div>

                <!-- Build Total -->
                <div class="build-total">
                    <span class="total-label">Estimated Total</span>
                    <div id="build-total-price">₹0</div>
                </div>

                <!-- Action Buttons -->
                <div class="summary-actions">
                    <button class="action-btn export-btn" onclick="exportBuild()">
                        📥 Export Build
                    </button>
                    <button class="action-btn reset-btn" onclick="resetBuild()">
                        🔄 Reset Build
                    </button>
                </div>
            </aside>
        </div>

        <!-- Help Section -->
        <section class="builder-help-section">
            <h2>How It Works</h2>
            <div class="help-grid">
                <div class="help-card">
                    <div class="help-icon">1️⃣</div>
                    <h3>Select Components</h3>
                    <p>Choose from our curated selection of high-quality PC components across all categories.</p>
                </div>
                <div class="help-card">
                    <div class="help-icon">✓</div>
                    <h3>Check Compatibility</h3>
                    <p>Our system automatically validates component compatibility to prevent conflicts.</p>
                </div>
                <div class="help-card">
                    <div class="help-icon">💰</div>
                    <h3>Track Pricing</h3>
                    <p>Indicative prices are shown in Indian Rupees. Use the retailer links to check current prices and availability before purchasing.</p>
                </div>
                <div class="help-card">
                    <div class="help-icon">📊</div>
                    <h3>Export Your Build</h3>
                    <p>Save and export your complete build configuration for future reference or purchasing.</p>
                </div>
            </div>
        </section>
    </main>
</div>

<?php
get_footer();
?>

<style>
    .pc-builder-main {
        width: 100%;
    }

    .pc-builder-header {
        position: relative;
        background-color: #0f172a;
        background-image: linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)), url('https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?auto=format&fit=crop&w=1600&q=80');
        background-position: center center;
        background-repeat: no-repeat;
        background-size: cover;
        color: white;
        margin-top: -96px;
        padding: 196px 40px 100px;
        text-align: center;
        margin-bottom: 40px;
    }

    .pc-builder-header h1 {
        font-size: 42px;
        font-weight: 700;
        margin: 0 0 15px 0;
        color: white;
    }

    .pc-builder-header p {
        font-size: 18px;
        margin: 0;
        opacity: 0.95;
    }

    .pc-builder-breadcrumb {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        color: white;
        font-size: 14px;
        margin-top: 20px;
    }

    .pc-builder-breadcrumb-separator {
        color: #FF4255;
        font-size: 20px;
        line-height: 1;
    }

    .pc-builder-left {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    /* Help Section */
    .builder-help-section {
        background: #f5f7fa;
        padding: 60px 40px;
        margin-top: 60px;
        border-radius: 12px;
    }

    .builder-help-section h2 {
        text-align: center;
        font-size: 32px;
        font-weight: 700;
        color: #272B36;
        margin-bottom: 40px;
    }

    .help-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .help-card {
        background: white;
        padding: 30px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .help-card:hover {
        box-shadow: 0 8px 20px rgba(255, 66, 85, 0.15);
        transform: translateY(-5px);
    }

    .help-icon {
        font-size: 48px;
        margin-bottom: 15px;
    }

    .help-card h3 {
        font-size: 18px;
        font-weight: 700;
        color: #272B36;
        margin: 0 0 10px 0;
    }

    .help-card p {
        font-size: 14px;
        color: #7A7A7A;
        margin: 0;
        line-height: 1.6;
    }

    @media (max-width: 768px) {
        .pc-builder-header {
            padding: 100px 20px 60px;
        }

        .pc-builder-header h1 {
            font-size: 28px;
        }

        .pc-builder-header p {
            font-size: 14px;
        }

        .builder-help-section {
            padding: 40px 20px;
        }

        .builder-help-section h2 {
            font-size: 24px;
        }
    }
</style>
