$(document).ready(function() {
    let dailyChart, statusChart, issueTypeChart;
    
    // Initialize dashboard
    initializeDashboard();
    
    // Auto-refresh every 30 seconds
    setInterval(refreshDashboard, 30000);
    
    function initializeDashboard() {
        loadDailyChart();
        loadStatusChart();
        loadIssueTypeChart();
        loadTechnicianPerformance();
        loadLocationStats();
    }
    
    function loadDailyChart() {
        $.ajax({
            url: 'api/daily_stats.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                const ctx = document.getElementById('dailyChart').getContext('2d');
                
                if (dailyChart) {
                    dailyChart.destroy();
                }
                
                dailyChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Tiket Baru',
                            data: data.data,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            },
            error: function() {
                console.error('Error loading daily chart');
            }
        });
    }
    
    function loadStatusChart() {
        $.ajax({
            url: 'api/status_stats.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                const ctx = document.getElementById('statusChart').getContext('2d');
                
                if (statusChart) {
                    statusChart.destroy();
                }
                
                statusChart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            data: data.data,
                            backgroundColor: [
                                '#f59e0b',
                                '#06b6d4',
                                '#10b981'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            },
            error: function() {
                console.error('Error loading status chart');
            }
        });
    }
    
    function loadIssueTypeChart() {
        const filter = $('#issueTypeFilter').val() || 'all';
        
        $.ajax({
            url: 'api/issue_stats.php',
            method: 'GET',
            data: { filter: filter },
            dataType: 'json',
            success: function(data) {
                const ctx = document.getElementById('issueTypeChart').getContext('2d');
                
                if (issueTypeChart) {
                    issueTypeChart.destroy();
                }
                
                issueTypeChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [{
                            label: 'Jumlah Gangguan',
                            data: data.data,
                            backgroundColor: '#3b82f6',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                });
            },
            error: function() {
                console.error('Error loading issue chart');
            }
        });
    }
    
    function loadTechnicianPerformance() {
        $.ajax({
            url: 'api/technician_performance.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                let html = '';
                data.forEach(function(tech, index) {
                    const badge = getPerformanceBadge(tech.avg_completion_time);
                    
                    html += `
                        <div class="performance-item">
                            <div>
                                <div class="performance-name">${tech.username}</div>
                                <div class="performance-subtitle">${tech.completed_tasks} tugas selesai</div>
                            </div>
                            <div style="text-align: right;">
                                <div class="performance-value">${tech.avg_completion_time}h</div>
                                <div class="performance-badge ${badge.class}">${badge.text}</div>
                            </div>
                        </div>
                    `;
                });
                $('#technicianPerformance').html(html);
            },
            error: function() {
                $('#technicianPerformance').html('<div class="loading">Error loading data</div>');
            }
        });
    }
    
    function loadLocationStats() {
        $.ajax({
            url: 'api/location_stats.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                let html = '';
                data.forEach(function(location) {
                    const severity = getSeverityLevel(location.ticket_count);
                    
                    html += `
                        <div class="performance-item">
                            <div>
                                <div class="performance-name">${location.alamat.substring(0, 30)}...</div>
                                <div class="performance-subtitle">${severity.text}</div>
                            </div>
                            <div style="text-align: right;">
                                <div class="performance-value">${location.ticket_count}</div>
                                <div class="performance-badge ${severity.class}">tiket</div>
                            </div>
                        </div>
                    `;
                });
                $('#locationStats').html(html);
            },
            error: function() {
                $('#locationStats').html('<div class="loading">Error loading data</div>');
            }
        });
    }
    
    function refreshDashboard() {
        $.ajax({
            url: 'api/dashboard_stats.php',
            method: 'GET',
            dataType: 'json',
            success: function(data) {
                animateValue('#totalTickets', data.totalTickets);
                animateValue('#openTickets', data.openTickets);
                animateValue('#onProgressTickets', data.onProgressTickets);
                animateValue('#completedTickets', data.completedTickets);
                
                $('#updateTime').text(new Date().toLocaleString('id-ID'));
            }
        });
        
        loadTechnicianPerformance();
        loadLocationStats();
    }
    
    function animateValue(selector, endValue) {
        const element = $(selector);
        const startValue = parseInt(element.text()) || 0;
        const duration = 1000;
        const startTime = performance.now();
        
        function updateValue(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const currentValue = Math.floor(startValue + (endValue - startValue) * progress);
            
            element.text(currentValue);
            
            if (progress < 1) {
                requestAnimationFrame(updateValue);
            }
        }
        
        requestAnimationFrame(updateValue);
    }
    
    function getPerformanceBadge(avgTime) {
        if (avgTime <= 4) {
            return { class: 'excellent', text: 'Excellent' };
        } else if (avgTime <= 8) {
            return { class: 'good', text: 'Good' };
        } else {
            return { class: 'average', text: 'Average' };
        }
    }
    
    function getSeverityLevel(ticketCount) {
        if (ticketCount >= 10) {
            return { class: 'average', text: 'Perlu perhatian' };
        } else if (ticketCount >= 5) {
            return { class: 'good', text: 'Monitoring ketat' };
        } else {
            return { class: 'excellent', text: 'Kondisi stabil' };
        }
    }
    
    // Event handlers
    $('#issueTypeFilter').change(function() {
        loadIssueTypeChart();
    });
    
    window.refreshDailyChart = function() {
        loadDailyChart();
    };
});
