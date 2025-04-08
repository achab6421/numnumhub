// 自定義JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // 自動關閉警告訊息
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const closeButton = alert.querySelector('.close');
            if (closeButton) {
                closeButton.click();
            }
        }, 5000); // 5秒後自動關閉
    });
});
