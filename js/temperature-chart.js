function initializeTemperatureChart(deviceId) {
    let chart = null;

    function updateChart(timespan) {
        const sensors = ['DS18B20_1', 'DS18B20_2', 'BMP180_TEMP', 'BMP180_PRESSURE'];
        const colors = ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'];
        
        Promise.all(sensors.map((sensor, index) => 
            fetch(`api/get_temperature_history.php?device_id=${deviceId}&sensor_type=${sensor}&timespan=${timespan}`)
                .then(response => response.json())
                .then(data => ({
                    label: data.data[0]?.sensor_type || sensor,
                    data: data.data.map(point => ({
                        x: new Date(point.timestamp),
                        y: point.value
                    })),
                    borderColor: colors[index],
                    tension: 0.4,
                    unit: data.data[0]?.unit || '°C'
                }))
        )).then(datasets => {
            if (chart) {
                chart.destroy();
            }

            const ctx = document.getElementById(`tempChart${deviceId}`).getContext('2d');
            chart = new Chart(ctx, {
                type: 'line',
                data: {
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        x: {
                            type: 'time',
                            time: {
                                unit: timespan.includes('d') ? 'day' : 'hour',
                                displayFormats: {
                                    hour: 'HH:mm',
                                    day: 'DD.MM'
                                }
                            },
                            title: {
                                display: true,
                                text: 'Zeit'
                            }
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Wert'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + 
                                           context.parsed.y.toFixed(1) + 
                                           context.dataset.unit;
                                }
                            }
                        }
                    }
                }
            });
        });
    }

    // Initialisiere Chart mit 24h
    updateChart('24h');

    // Mache die updateChart-Funktion global verfügbar
    window[`updateChart${deviceId}`] = updateChart;
}
