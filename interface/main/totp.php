<?php
require_once("../globals.php");
use OpenEMR\Core\Header;
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo xlt('TOTP Authentication'); ?></title>
    <?php Header::setupHeader(); ?>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
            background-color: #f5f6fa !important;
            margin: 0 !important;
            padding: 0 !important;
            display: flex !important;
            justify-content: center !important;
            align-items: center !important;
            min-height: 100vh !important;
            color: #2c3e50 !important;
        }

        .totp-container {
            background-color: #ffffff !important;
            padding: 2rem !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
            width: 100% !important;
            max-width: 500px !important;
            margin: 1rem !important;
        }

        .totp-header {
            text-align: center !important;
            margin-bottom: 2rem !important;
        }

        .totp-header h1 {
            color: #2c3e50 !important;
            font-size: 1.8rem !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .totp-header p {
            color: #666 !important;
            margin-top: 0.5rem !important;
        }

        .form-group {
            margin-bottom: 1.5rem !important;
        }

        .form-group label {
            display: block !important;
            margin-bottom: 0.5rem !important;
            font-weight: 500 !important;
            color: #2c3e50 !important;
        }

        .form-control {
            width: 100% !important;
            padding: 0.75rem !important;
            font-size: 1.1rem !important;
            border: 2px solid #e0e0e0 !important;
            border-radius: 8px !important;
            transition: border-color 0.3s ease !important;
            box-sizing: border-box !important;
        }

        .form-control:focus {
            outline: none !important;
            border-color: #3498db !important;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1) !important;
        }

        .btn {
            display: inline-block !important;
            padding: 0.75rem 1.5rem !important;
            font-size: 1rem !important;
            font-weight: 600 !important;
            text-align: center !important;
            text-decoration: none !important;
            border-radius: 8px !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            border: none !important;
            width: 100% !important;
        }

        .btn-primary {
            background-color: #3498db !important;
            color: white !important;
        }

        .btn-primary:hover {
            background-color: #2980b9 !important;
            transform: translateY(-1px) !important;
        }

        .btn-primary:active {
            transform: translateY(0) !important;
        }

        .totp-icon {
            text-align: center !important;
            margin-bottom: 1.5rem !important;
        }

        .totp-icon img {
            width: 64px;
            height: 64px;
        }

        .help-text {
            font-size: 0.9rem !important;
            color: #666 !important;
            margin-top: 0.5rem !important;
        }

        @media (max-width: 576px) {
            .totp-container {
                margin: 1rem !important;
                padding: 1.5rem !important;
            }

            .totp-header h1 {
                font-size: 1.5rem !important;
            }
        }
    </style>
</head>
<body>
    <div class="totp-container">
        <div class="totp-header">
            <div class="totp-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12C2 17.52 6.48 22 12 22C17.52 22 22 17.52 22 12C22 6.48 17.52 2 12 2ZM12 20C7.59 20 4 16.41 4 12C4 7.59 7.59 4 12 4C16.41 4 20 7.59 20 12C20 16.41 16.41 20 12 20Z" fill="#3498db"/>
                    <path d="M12.5 7H11V13L16.2 16.2L17 14.9L12.5 12.2V7Z" fill="#3498db"/>
                </svg>
            </div>
            <h1><?php echo xlt('Two-Factor Authentication'); ?></h1>
            <p><?php echo xlt('Please enter the code from your authenticator app'); ?></p>
        </div>

        <form method="post" action="main_screen.php?auth=login&site=default" target="_top" name="challenge_form" id="challenge_form">
            <div class="form-group">
                <label for="totp"><?php echo xlt('Authentication Code'); ?></label>
                <input type="text" 
                       name="totp" 
                       class="form-control" 
                       id="totp" 
                       maxlength="12" 
                       required 
                       autocomplete="off"
                       autofocus
                       pattern="[0-9]*"
                       inputmode="numeric"
                       placeholder="<?php echo xla('Enter 6-digit code'); ?>">
                <div class="help-text"><?php echo xlt('Enter the 6-digit code from your authenticator app'); ?></div>
            </div>

            <input type="hidden" name="form_response" value="true">
            <input type="hidden" name="new_login_session_management" value="1">
            <input type="hidden" name="languageChoice" value="1">
            <input type="hidden" name="authUser" value="<?php echo attr($_POST['authUser'] ?? ''); ?>">
            <input type="hidden" name="clearPass" value="<?php echo attr($_POST['clearPass'] ?? ''); ?>">

            <button type="submit" class="btn btn-primary">
                <?php echo xlt('Verify Code'); ?>
            </button>
        </form>
    </div>

    <script>
        // Auto-focus the input field
        document.getElementById('totp').focus();

        // Format input to only allow numbers
        document.getElementById('totp').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>
</body>
</html> 