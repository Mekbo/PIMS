/* 用户登录js脚本
 * login.js
 * 2025-03-18
 * by Arlvin.Ceon
*/

document.addEventListener('DOMContentLoaded', function() {
            let loginAttempts = 0;
            let captchaCode = '';
            
            // 生成验证码
            function generateCaptcha() {
                captchaCode = '';
                for (let i = 0; i < 4; i++) {
                    captchaCode += Math.floor(Math.random() * 10);
                }
                document.getElementById('captchaImage').textContent = captchaCode;
                return captchaCode;
            }
            
            // 登录表单提交
            document.getElementById('loginForm').addEventListener('submit', function(e) {
                e.preventDefault();
                console.log('登录表单提交');
                
                const username = document.getElementById('username').value;
                const password = document.getElementById('password').value;
                const captchaInput = document.getElementById('captcha').value;
                
                console.log('用户名:', username);
                console.log('密码长度:', password.length);
                
                // 验证码检查
                if (loginAttempts >= 3) {
                    if (!captchaInput) {
                        document.getElementById('login-error').style.display = 'block';
                        document.getElementById('login-error').textContent = '请输入验证码';
                        console.log('验证码为空');
                        return;
                    }
                    if (captchaInput !== captchaCode) {
                        document.getElementById('login-error').style.display = 'block';
                        document.getElementById('login-error').textContent = '验证码错误';
                        generateCaptcha();
                        console.log('验证码错误');
                        return;
                    }
                }
                
                console.log('发送登录请求');
                // 发送登录请求
                fetch('core/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}`
                })
                .then(response => {
                    console.log('收到响应:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('响应数据:', data);
                    if (data.success) {
                        console.log('登录成功，准备跳转');
                        window.location.href = 'index.php?page=main';
                    } else {
                        loginAttempts++;
                        document.getElementById('login-error').style.display = 'block';
                        document.getElementById('login-error').textContent = data.message || '用户名或密码错误';
                        console.log('登录失败:', data.message);
                        
                        // 显示验证码
                        if (loginAttempts >= 3) {
                            document.getElementById('captchaGroup').style.display = 'block';
                            generateCaptcha();
                            console.log('显示验证码');
                        }
                    }
                })
                .catch(error => {
                    document.getElementById('login-error').style.display = 'block';
                    document.getElementById('login-error').textContent = '登录请求失败，请稍后再试';
                    console.error('Error:', error);
                });
            });
        });