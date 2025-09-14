/**
 * Business Intelligence Dashboard
 * Advanced Analytics and Reporting Interface
 */

class BIDashboard {
    constructor(options = {}) {
        this.options = {
            apiUrl: '/api/analytics',
            refreshInterval: 300000, // 5 minutes
            autoRefresh: true,
            theme: 'light',
            ...options
        };

        this.authToken = null;
        this.refreshTimer = null;
        this.charts = {};
        this.currentPeriod = 'today';
        this.isLoading = false;

        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeChartLibrary();
    }

    setAuthToken(token) {
        this.authToken = token;
        return this;
    }

    setupEventListeners() {
        // Period selector
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('period-selector')) {
                this.currentPeriod = e.target.value;
                this.loadDashboard();
            }
        });

        // Refresh button
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('refresh-dashboard')) {
                e.preventDefault();
                this.loadDashboard(true);
            }
        });

        // Export buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('export-report')) {
                e.preventDefault();
                const reportType = e.target.dataset.reportType;
                const format = e.target.dataset.format || 'json';
                this.exportReport(reportType, format);
            }
        });

        // Auto-refresh toggle
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('auto-refresh-toggle')) {
                this.options.autoRefresh = e.target.checked;
                if (this.options.autoRefresh) {
                    this.startAutoRefresh();
                } else {
                    this.stopAutoRefresh();
                }
            }
        });
    }

    initializeChartLibrary() {
        // Initialize Chart.js or preferred charting library
        if (typeof Chart !== 'undefined') {
            Chart.defaults.color = this.options.theme === 'dark' ? '#ffffff' : '#666666';
            Chart.defaults.borderColor = this.options.theme === 'dark' ? '#374151' : '#e5e7eb';
        }
    }

    async loadDashboard(forceRefresh = false) {
        if (this.isLoading) return;

        try {
            this.isLoading = true;
            this.showLoadingState();

            const queryParams = new URLSearchParams({
                period: this.currentPeriod,
                refresh: forceRefresh ? '1' : '0'
            });

            const response = await this.apiCall('GET', `/dashboard?${queryParams}`);

            if (response.success) {
                this.renderDashboard(response.dashboard);
                this.updateLastRefreshed(response.last_updated);
            } else {
                this.showError('Failed to load dashboard data');
            }
        } catch (error) {
            this.showError('Network error loading dashboard');
            console.error('Dashboard loading error:', error);
        } finally {
            this.isLoading = false;
            this.hideLoadingState();
        }
    }

    renderDashboard(data) {
        // Render key metrics cards
        this.renderMetricCards(data);

        // Render charts
        this.renderUserMetrics(data.users);
        this.renderJobMetrics(data.jobs);
        this.renderTrafficMetrics(data.traffic);
        this.renderEngagementMetrics(data.engagement);
        this.renderRevenueMetrics(data.revenue);

        // Update period display
        this.updatePeriodDisplay();
    }

    renderMetricCards(data) {
        const metricsContainer = document.getElementById('metrics-cards');
        if (!metricsContainer) return;

        const metrics = [
            {
                title: 'Total Users',
                value: data.users?.total_users || 0,
                change: data.users?.growth_rate || 0,
                icon: '👥',
                color: 'blue'
            },
            {
                title: 'Active Jobs',
                value: data.jobs?.total_active_jobs || 0,
                change: data.jobs?.job_posting_trend?.length > 1 ?
                    this.calculateTrendChange(data.jobs.job_posting_trend) : 0,
                icon: '💼',
                color: 'green'
            },
            {
                title: 'Applications',
                value: data.applications?.total_applications || 0,
                change: data.applications?.growth_rate || 0,
                icon: '📋',
                color: 'purple'
            },
            {
                title: 'Page Views',
                value: data.traffic?.total_page_views || 0,
                change: 0,
                icon: '👁️',
                color: 'orange'
            }
        ];

        metricsContainer.innerHTML = metrics.map(metric => `
            <div class="metric-card metric-card--${metric.color}">
                <div class="metric-card__icon">${metric.icon}</div>
                <div class="metric-card__content">
                    <h3 class="metric-card__title">${metric.title}</h3>
                    <div class="metric-card__value">${this.formatNumber(metric.value)}</div>
                    <div class="metric-card__change ${metric.change >= 0 ? 'positive' : 'negative'}">
                        ${metric.change >= 0 ? '↗' : '↘'} ${Math.abs(metric.change).toFixed(1)}%
                    </div>
                </div>
            </div>
        `).join('');
    }

    renderUserMetrics(userData) {
        // User growth chart
        const userChartCtx = document.getElementById('userGrowthChart')?.getContext('2d');
        if (userChartCtx) {
            if (this.charts.userGrowth) {
                this.charts.userGrowth.destroy();
            }

            this.charts.userGrowth = new Chart(userChartCtx, {
                type: 'line',
                data: {
                    labels: this.getDateLabels(),
                    datasets: [{
                        label: 'New Users',
                        data: [userData.new_users || 0], // Would need time series data
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'User Growth Trend'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // User type breakdown
        const userTypeCtx = document.getElementById('userTypeChart')?.getContext('2d');
        if (userTypeCtx && userData) {
            if (this.charts.userType) {
                this.charts.userType.destroy();
            }

            this.charts.userType = new Chart(userTypeCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Job Seekers', 'Employers'],
                    datasets: [{
                        data: [userData.job_seekers || 0, userData.employers || 0],
                        backgroundColor: ['#10B981', '#F59E0B'],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'User Type Distribution'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    renderJobMetrics(jobData) {
        // Job performance metrics
        const jobPerformanceCtx = document.getElementById('jobPerformanceChart')?.getContext('2d');
        if (jobPerformanceCtx && jobData) {
            if (this.charts.jobPerformance) {
                this.charts.jobPerformance.destroy();
            }

            this.charts.jobPerformance = new Chart(jobPerformanceCtx, {
                type: 'bar',
                data: {
                    labels: ['Views', 'Applications', 'Saves'],
                    datasets: [{
                        label: 'Average per Job',
                        data: [
                            jobData.avg_views_per_job || 0,
                            jobData.avg_applications_per_job || 0,
                            0 // Would need saves data
                        ],
                        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B'],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Job Performance Metrics'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }

    renderTrafficMetrics(trafficData) {
        // Traffic sources chart
        const trafficCtx = document.getElementById('trafficSourceChart')?.getContext('2d');
        if (trafficCtx && trafficData?.traffic_sources) {
            if (this.charts.trafficSources) {
                this.charts.trafficSources.destroy();
            }

            const sources = trafficData.traffic_sources;
            this.charts.trafficSources = new Chart(trafficCtx, {
                type: 'pie',
                data: {
                    labels: Object.keys(sources),
                    datasets: [{
                        data: Object.values(sources),
                        backgroundColor: [
                            '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Traffic Sources'
                        }
                    }
                }
            });
        }

        // Device breakdown
        if (trafficData?.device_breakdown) {
            this.renderDeviceBreakdown(trafficData.device_breakdown);
        }
    }

    renderEngagementMetrics(engagementData) {
        const engagementCtx = document.getElementById('engagementChart')?.getContext('2d');
        if (engagementCtx && engagementData) {
            if (this.charts.engagement) {
                this.charts.engagement.destroy();
            }

            this.charts.engagement = new Chart(engagementCtx, {
                type: 'radar',
                data: {
                    labels: ['Job Views', 'Searches', 'Applications', 'Saves', 'Profile Updates'],
                    datasets: [{
                        label: 'User Engagement',
                        data: [
                            this.normalizeValue(engagementData.job_views),
                            this.normalizeValue(engagementData.searches_performed),
                            this.normalizeValue(engagementData.total_applications || 0),
                            this.normalizeValue(engagementData.job_saves),
                            this.normalizeValue(50) // Mock profile updates
                        ],
                        fill: true,
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        borderColor: '#3B82F6',
                        pointBackgroundColor: '#3B82F6'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'User Engagement Overview'
                        }
                    },
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }
    }

    renderRevenueMetrics(revenueData) {
        // Revenue chart would go here
        // Currently just update revenue display
        const revenueDisplay = document.getElementById('revenue-display');
        if (revenueDisplay && revenueData) {
            revenueDisplay.innerHTML = `
                <div class="revenue-summary">
                    <h3>Revenue Overview</h3>
                    <div class="revenue-item">
                        <span>Total Revenue:</span>
                        <span>$${this.formatNumber(revenueData.total_revenue || 0)}</span>
                    </div>
                    <div class="revenue-item">
                        <span>MRR:</span>
                        <span>$${this.formatNumber(revenueData.mrr || 0)}</span>
                    </div>
                    <div class="revenue-item">
                        <span>ARPU:</span>
                        <span>$${revenueData.arpu || 0}</span>
                    </div>
                </div>
            `;
        }
    }

    renderDeviceBreakdown(deviceData) {
        const deviceContainer = document.getElementById('device-breakdown');
        if (!deviceContainer) return;

        const total = Object.values(deviceData).reduce((a, b) => a + b, 0);

        deviceContainer.innerHTML = Object.entries(deviceData)
            .map(([device, count]) => {
                const percentage = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
                return `
                    <div class="device-item">
                        <div class="device-name">${this.capitalizeFirst(device)}</div>
                        <div class="device-bar">
                            <div class="device-bar-fill" style="width: ${percentage}%"></div>
                        </div>
                        <div class="device-percentage">${percentage}%</div>
                    </div>
                `;
            }).join('');
    }

    async exportReport(reportType, format) {
        try {
            const response = await this.apiCall('POST', '/reports', {
                report_type: reportType,
                format: format,
                parameters: {
                    period: this.currentPeriod
                }
            });

            if (format === 'json') {
                this.downloadJson(response.report, `${reportType}_report.json`);
            } else {
                // Handle other formats (CSV, PDF)
                this.showSuccess(`${reportType} report generated successfully`);
            }
        } catch (error) {
            this.showError(`Failed to generate ${reportType} report`);
        }
    }

    startAutoRefresh() {
        this.stopAutoRefresh();
        this.refreshTimer = setInterval(() => {
            this.loadDashboard();
        }, this.options.refreshInterval);
    }

    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    async apiCall(method, endpoint, data = null) {
        const url = `${this.options.apiUrl}${endpoint}`;
        const config = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...(this.authToken && { 'Authorization': `Bearer ${this.authToken}` })
            }
        };

        if (data && (method === 'POST' || method === 'PUT')) {
            config.body = JSON.stringify(data);
        }

        const response = await fetch(url, config);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    // Utility methods
    formatNumber(num) {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    normalizeValue(value, max = 100) {
        return Math.min((value / max) * 100, 100);
    }

    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    calculateTrendChange(trendData) {
        if (!trendData || trendData.length < 2) return 0;
        const values = Object.values(trendData);
        const current = values[values.length - 1];
        const previous = values[values.length - 2];
        return previous > 0 ? ((current - previous) / previous) * 100 : 0;
    }

    getDateLabels() {
        // Generate date labels based on current period
        const labels = [];
        const today = new Date();

        switch (this.currentPeriod) {
            case 'today':
                for (let i = 23; i >= 0; i--) {
                    labels.push(`${23 - i}:00`);
                }
                break;
            case 'week':
                for (let i = 6; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(date.getDate() - i);
                    labels.push(date.toLocaleDateString('en', { weekday: 'short' }));
                }
                break;
            case 'month':
                for (let i = 29; i >= 0; i--) {
                    const date = new Date(today);
                    date.setDate(date.getDate() - i);
                    labels.push(date.getDate().toString());
                }
                break;
        }

        return labels;
    }

    updatePeriodDisplay() {
        const periodDisplay = document.getElementById('current-period');
        if (periodDisplay) {
            periodDisplay.textContent = this.currentPeriod.charAt(0).toUpperCase() + this.currentPeriod.slice(1);
        }
    }

    updateLastRefreshed(timestamp) {
        const lastRefreshedEl = document.getElementById('last-refreshed');
        if (lastRefreshedEl) {
            const date = new Date(timestamp);
            lastRefreshedEl.textContent = `Last updated: ${date.toLocaleTimeString()}`;
        }
    }

    showLoadingState() {
        const loadingEl = document.getElementById('loading-indicator');
        if (loadingEl) {
            loadingEl.style.display = 'block';
        }
    }

    hideLoadingState() {
        const loadingEl = document.getElementById('loading-indicator');
        if (loadingEl) {
            loadingEl.style.display = 'none';
        }
    }

    showError(message) {
        // Implementation depends on your notification system
        console.error('Dashboard Error:', message);
        alert(message); // Replace with proper notification
    }

    showSuccess(message) {
        // Implementation depends on your notification system
        console.log('Dashboard Success:', message);
    }

    downloadJson(data, filename) {
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }

    destroy() {
        this.stopAutoRefresh();
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.charts = {};
    }
}

// Export for use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BIDashboard;
} else if (typeof window !== 'undefined') {
    window.BIDashboard = BIDashboard;
}

// Usage Example:
/*
const dashboard = new BIDashboard({
    apiUrl: '/api/analytics',
    autoRefresh: true,
    refreshInterval: 300000 // 5 minutes
});

dashboard.setAuthToken('your-auth-token');
dashboard.loadDashboard();
*/