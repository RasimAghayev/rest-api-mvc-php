<?php
require_once 'config/config.php';
require_once 'helpers/index.php';
spl_autoload_register(function($className) {
    $parts = explode('\\', $className);
    if(count($parts) > 1) {
        $classfile = 'libraries'. DIRECTORY_SEPARATOR .'OtherClass'. DIRECTORY_SEPARATOR .array_shift($parts) . DIRECTORY_SEPARATOR . strtolower(implode(DIRECTORY_SEPARATOR, $parts)) . ".php";
    } else {
        // Was strtolower($className) — every top-level libraries/ file is
        // PascalCase (Core.php, Controller.php, Database.php), so this only
        // ever worked on case-insensitive filesystems (Windows/macOS). On
        // Linux — i.e. inside this Docker image — it 404'd on every request
        // before ever reaching the requested controller. Discovered while
        // verifying the DEC-R1-001 Docker redo end-to-end (curl a real
        // route through the running stack), not part of the Docker scope
        // itself, but there's no point shipping a container that 500s on
        // every request it serves.
        $classfile = 'libraries' . DIRECTORY_SEPARATOR . $className . '.php';
        // app/Services/ and app/Middleware/ (added by later refactor commits:
        // OTPService, JwtAuthMiddleware) were never wired into this
        // autoloader at all — it only ever looked under libraries/ — so
        // both were structurally unreachable dead code regardless of the
        // casing bug above. Checked here, before the OtherClass fallback,
        // since that fallback also assumes libraries/. Discovered the same
        // way as the casing fix: curling a real route through the
        // DEC-R1-001 Docker redo's running stack.
        if (FALSE === stream_resolve_include_path($classfile)) {
            foreach (['Services', 'Middleware'] as $extraDir) {
                $candidate = $extraDir . DIRECTORY_SEPARATOR . $className . '.php';
                if (FALSE !== stream_resolve_include_path($candidate)) {
                    $classfile = $candidate;
                    break;
                }
            }
        }
    }
    if(FALSE === stream_resolve_include_path($classfile)){
        // Was strtolower($className) — OtherClass/ files are also PascalCase
        // (GoogleAuthenticator.php, ValidField.php); same case-sensitivity
        // bug as the libraries/ branch above, hit via OTPService's
        // dependency on GoogleAuthenticator while verifying the Docker redo.
        $classfile = 'libraries' . DIRECTORY_SEPARATOR . 'OtherClass'. DIRECTORY_SEPARATOR . $className . '.php';
    }
    require_once $classfile;
});
