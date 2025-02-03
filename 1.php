<?php
session_start();

// Установим пароль по умолчанию
$admin_password = 'art';
if (file_exists('password.txt')) {
    $admin_password = trim(file_get_contents('password.txt'));
}

// Авторизация
if (isset($_POST['password']) && $_POST['password'] === $admin_password) {
    $_SESSION['logged_in'] = true;
    header("Location: 1.php");
    exit;
}

// Смена пароля
if (isset($_POST['new_password']) && isset($_SESSION['logged_in'])) {
    $new_password = trim($_POST['new_password']);
    file_put_contents('password.txt', $new_password);
    echo "Пароль обновлён!";
    exit;
}

// Сохранение изменений в HTML
if (isset($_POST['page']) && isset($_POST['content']) && isset($_SESSION['logged_in'])) {
    $page = $_POST['page'];
    $newContent = $_POST['content'];

    // Удаляем атрибуты contenteditable и class="editable"
    $newContent = preg_replace('/ contenteditable="true"/', '', $newContent);
    $newContent = preg_replace('/ class="editable"/', '', $newContent);

    if (file_exists($page)) {
        $content = file_get_contents($page);
        $content = preg_replace('/<body.*?>(.*?)<\/body>/is', '<body>' . $newContent . '</body>', $content);
        file_put_contents($page, $content);
        echo "Изменения сохранены!";
    }
    exit;
}

// Загрузка изображения
if (isset($_FILES['image']) && isset($_POST['image_path']) && isset($_SESSION['logged_in'])) {
    $target = $_POST['image_path'];
    $uploadPath = __DIR__ . '/' . $target;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
        echo "Изображение обновлено!";
    } else {
        echo "Ошибка загрузки изображения.";
    }
    exit;
}

// Проверка авторизации
if (!isset($_SESSION['logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Авторизация</title>
    </head>
    <body  style="
    margin: 0px !important;
">
        
        <style>
        .button.big {
            min-width: 280px;
    min-height: 60px;
    font-size: 22px;
    border-radius: 50px;
    background-color: #ffff00;
    border: none;
    transition: 500ms;
    cursor: pointer;
}

.button.big:hover {
    box-shadow: inset 0 0 0 2px #4354ff, inset 0 -60px 0 0 #fff;
    border: none;
}









    </style>
        <form method="post" style="
    background: black;
    color: yellow;
    font-family: monospace;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    margin: 0;
    flex-direction: column;
    gap: 20px;
    font-size: 30px;
">
            <label for="password" style="
    min-width: 240px;
    min-height: 40px;
    border-radius: 50px;
    border: #FFC107 1px;
">ФРИЛАНС ПРОКАЧКА</label>
            <input type="password" id="password" name="password" placeholder="Введите пароль" required style="
        min-width: 250px;
    min-height: 50px;
    border-radius: 50px;
    border-color: #ffff00;
    border-style: solid;
    padding: 2px;
    background-color: #000000;
    font-size: 17px;
    padding-left: 30px;
    color: #ffff00;
};
">
            <button type="submit" class="button big w-button">Войти</button>
        </form>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ФРИЛАНС ПРОКАЧКА</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .admin-panel {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            background: #333;
            color: white;
            padding: 10px;
            z-index: 1000;
        }
        .admin-panel select, .admin-panel button {
            margin: 5px;
            color: black;
        }
        #content { margin-top: 80px; }
        .editable:hover { outline: 2px dashed red; cursor: text; }
        img:hover { outline: 2px dashed blue; cursor: pointer; }
        .modal {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            border: 1px solid #ccc;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            display: none;
        }
        .btn {
display: none;
    min-width: 200px;
    min-height: 40px;
    font-size: 14px;
    border-radius: 50px;
    background-color: rgb(255, 255, 0);
    border: none;
    transition: 500ms;
    cursor: pointer;
}
.btn:hover {
box-shadow: inset 0 0 0 2px #4354ff, inset 0 -60px 0 0 #fff;
}
    </style>
</head>
<body>
<div class="admin-panel">
    <button id="change-password-btn" style="display: none;">Сменить пароль</button>
    <select id="page-selector" style="min-height: 40px;
    border-radius: 50px;
    padding-left: 20px;">
        <?php
        $files = array_diff(scandir('.'), ['.', '..', '1.php', 'password.txt']);
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'html') {
                echo "<option value=\"$file\">$file</option>";
            }
        }
        ?>
    </select>
    <button id="save-btn" class="btn" >Сохранить изменения</button>
</div>

<div id="content"></div>

<div id="password-modal" class="modal">
    <label for="new-password">Новый пароль:</label>
    <input type="password" id="new-password">
    <button onclick="saveNewPassword()">Сохранить</button>
</div>

<script>
    document.getElementById('change-password-btn').addEventListener('click', function() {
        document.getElementById('password-modal').style.display = 'block';
    });

    function saveNewPassword() {
        const newPassword = document.getElementById('new-password').value;
        if (newPassword) {
            const formData = new FormData();
            formData.append('new_password', newPassword);
            fetch('1.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                alert('Пароль обновлён!');
                document.getElementById('password-modal').style.display = 'none';
            });
        }
    }

    document.getElementById('page-selector').addEventListener('change', loadPage);

    function loadPage() {
        const page = document.getElementById('page-selector').value;
        fetch(page)
            .then(response => response.text())
            .then(data => {
                document.getElementById('content').innerHTML = data;
                enableEditing();
            });
    }

    function enableEditing() {
        const traverseAndMakeEditable = (node) => {
            if (node.nodeType === Node.TEXT_NODE && node.nodeValue.trim()) {
                const wrapper = document.createElement('span');
                wrapper.classList.add('editable');
                wrapper.setAttribute('contenteditable', 'true');
                wrapper.textContent = node.nodeValue;
                wrapper.addEventListener('input', () => {
                    document.getElementById('save-btn').style.display = 'inline-block';
                });
                node.replaceWith(wrapper);
            } else if (node.nodeType === Node.ELEMENT_NODE) {
                node.childNodes.forEach(traverseAndMakeEditable);
                if (node.tagName === 'IMG') {
                    node.addEventListener('click', () => uploadImage(node));
                }
            }
        };

        const content = document.getElementById('content');
        traverseAndMakeEditable(content);
    }

    function uploadImage(imgElement) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.addEventListener('change', () => {
            const file = input.files[0];
            const formData = new FormData();
            formData.append('image', file);
            formData.append('image_path', imgElement.getAttribute('src'));
            fetch('1.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                imgElement.src = URL.createObjectURL(file);
                alert('Изображение обновлено!');
            });
        });
        input.click();
    }

    document.getElementById('save-btn').addEventListener('click', () => {
        const page = document.getElementById('page-selector').value;
        const content = document.getElementById('content').innerHTML;
        const formData = new FormData();
        formData.append('page', page);
        formData.append('content', content);

        fetch('1.php', {
            method: 'POST',
            body: formData
        }).then(() => {
            alert('Изменения сохранены!');
            document.getElementById('save-btn').style.display = 'none';
        });
    });

    loadPage();
</script>
</body>
</html>
