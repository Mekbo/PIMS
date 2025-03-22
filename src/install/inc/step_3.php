<div class="import-database">
    <h2>数据导入</h2>
    
    <div class="progress-container">
        <div class="progress-bar" id="progressBar">
            <div class="progress-inner" style="width: 0%"></div>
        </div>
        <div class="progress-text" id="progressText">准备导入数据...</div>
    </div>
    
    <form method="post" action="index.php?step=3" id="importForm">
        <div class="actions">
            <a href="index.php?step=2" class="button prev">上一步</a>
            <button type="submit" class="button next">开始导入</button>
        </div>
    </form>
</div>

<script>
document.getElementById('importForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var progressBar = document.getElementById('progressBar').querySelector('.progress-inner');
    var progressText = document.getElementById('progressText');
    var submitButton = this.querySelector('button[type="submit"]');
    
    // 禁用按钮，防止重复提交
    submitButton.disabled = true;
    submitButton.textContent = '导入中...';
    
    // 模拟进度
    var progress = 0;
    var interval = setInterval(function() {
        progress += 5;
        if (progress > 90) {
            clearInterval(interval);
        }
        progressBar.style.width = progress + '%';
        progressText.textContent = '导入中，请稍候... ' + progress + '%';
    }, 200);
    
    // 发送请求
    fetch('index.php?step=3', {
        method: 'POST',
        body: new FormData(this),
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        if (!response.ok || !contentType || !contentType.includes('application/json')) {
            throw new Error('服务器返回格式错误');
        }
        return response.json();
    })
    .then(data => {
        clearInterval(interval);
        
        if (data.status) {
            progressBar.style.width = '100%';
            progressText.textContent = '导入完成！';
            setTimeout(function() {
                window.location.href = 'index.php?step=4';
            }, 1000);
        } else {
            progressBar.style.width = '0%';
            progressText.textContent = '导入失败：' + data.message;
            submitButton.disabled = false;
            submitButton.textContent = '重试';
        }
    })
    .catch(error => {
        clearInterval(interval);
        progressBar.style.width = '0%';
        progressText.textContent = '导入出错：' + error.message;
        submitButton.disabled = false;
        submitButton.textContent = '重试';
    });
});