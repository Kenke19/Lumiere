
    document.addEventListener('DOMContentLoaded', () => {
  let lineChart, areaChart, donutChart, barChart;

  // Fetch data from backend API with optional period param
  async function fetchData(period = 'daily') {
    try {
      const res = await fetch(`../api/dashboard-data.php?period=${period}`);
      if (!res.ok) throw new Error('Network error');
      return await res.json();
    } catch (err) {
      console.error('Failed to fetch chart data:', err);
      return null;
    }
  }

  // Helper to get CSS variables for consistent colors
  function getCssVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name) || '#000';
  }

  // Create or update the Sales Over Time line chart
  function createOrUpdateLineChart(labels, data) {
    const ctx = document.getElementById('lineChart').getContext('2d');
    if (lineChart) {
      lineChart.data.labels = labels;
      lineChart.data.datasets[0].data = data;
      lineChart.update();
    } else {
      lineChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Sales',
            data,
            borderColor: '#6366f1', // Indigo-500
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 4,
          }]
        },
        options: {
          responsive: true,
          scales: {
            x: { ticks: { color: getCssVar('--text-light') } },
            y: { ticks: { color: getCssVar('--text-light') }, beginAtZero: true }
          },
          plugins: {
            legend: { labels: { color: getCssVar('--text') } }
          }
        }
      });
    }
  }

  // Create or update Customer Growth area chart
  function createOrUpdateAreaChart(labels, data) {
    const ctx = document.getElementById('areaChart').getContext('2d');
    if (areaChart) {
      areaChart.data.labels = labels;
      areaChart.data.datasets[0].data = data;
      areaChart.update();
    } else {
      areaChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'New Customers',
            data,
            borderColor: '#10b981', // Green-500
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            fill: true,
            tension: 0.4,
            borderWidth: 2,
            pointRadius: 4,
          }]
        },
        options: {
          responsive: true,
          scales: {
            x: { ticks: { color: getCssVar('--text-light') } },
            y: { ticks: { color: getCssVar('--text-light') }, beginAtZero: true }
          },
          plugins: {
            legend: { labels: { color: getCssVar('--text') } }
          }
        }
      });
    }
  }

  // Create or update Orders by Category donut chart
  function createOrUpdateDonutChart(labels, data) {
    const ctx = document.getElementById('donutChart').getContext('2d');
    if (donutChart) {
      donutChart.data.labels = labels;
      donutChart.data.datasets[0].data = data;
      donutChart.update();
    } else {
      donutChart = new Chart(ctx, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Orders by Category',
          data,
          borderColor: '#6366f1',
          backgroundColor: 'rgba(99, 102, 241, 0.2)', // for area chart fill
          fill: true, // set to true for area chart effect
          tension: 0.3,
          borderWidth: 2,
          pointRadius: 4,
          }]
        },
        options: {
          responsive: true,
          scales: {
          x: {
            title: { display: true, text: 'Category' },
            ticks: { color: getCssVar('--text-light') }
          },
          y: {
            title: { display: true, text: 'Order Count' },
            ticks: { color: getCssVar('--text-light') },
            beginAtZero: true
          }
        },
        plugins: {
          legend: { labels: { color: getCssVar('--text') } }
        }
        }
      });
    }
  }

  // Create or update Top Selling Products bar chart
  function createOrUpdateBarChart(labels, data) {
    const ctx = document.getElementById('barChart').getContext('2d');
    if (barChart) {
      barChart.data.labels = labels;
      barChart.data.datasets[0].data = data;
      barChart.update();
    } else {
      barChart = new Chart(ctx, {
        type: 'bar',
        data: {
          labels,
          datasets: [{
            label: 'Quantity Sold',
            data,
            backgroundColor: ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981'],
            borderRadius: 6,
            borderSkipped: false,
          }]
        },
        options: {
          responsive: true,
          scales: {
            x: { ticks: { color: getCssVar('--text-light') }, grid: { display: false } },
            y: { ticks: { color: getCssVar('--text-light') }, beginAtZero: true, grid: { color: getCssVar('--border') } }
          },
          plugins: { legend: { display: false } }
        }
      });
    }
  }

  // Load all charts with data for the given period
  async function loadCharts(period = 'daily') {
    const data = await fetchData(period);
    if (!data) return;

    createOrUpdateLineChart(
      data.salesOverTime.map(d => d.period_label),
      data.salesOverTime.map(d => parseFloat(d.sales))
    );

    createOrUpdateAreaChart(
      data.customerGrowth.map(d => d.period_label),
      data.customerGrowth.map(d => parseInt(d.new_customers))
    );

    createOrUpdateDonutChart(
      data.ordersByCategory.map(d => d.category_name),
      data.ordersByCategory.map(d => parseInt(d.order_count))
    );

    createOrUpdateBarChart(
      data.topProducts.map(d => d.product_name),
      data.topProducts.map(d => parseInt(d.total_quantity_sold))
    );
  }

  // Event listeners for period buttons (only for the lineChart)
  document.querySelectorAll('.chart-btn[data-chart="lineChart"]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.chart-btn[data-chart="lineChart"]').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      loadCharts(btn.dataset.period);
    });
  });

  // Initial chart load
  loadCharts();
});
