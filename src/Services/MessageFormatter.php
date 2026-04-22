<?php

namespace App\Services;

/**
 * MessageFormatter - Servicio para generar mensajes amables y formateados
 * Centraliza el formato de mensajes en la aplicación
 */
class MessageFormatter
{
    /**
     * Tipos de mensaje disponibles
     */
    public const TYPE_SUCCESS = 'success';
    public const TYPE_WARNING = 'warning';
    public const TYPE_ERROR   = 'error';
    public const TYPE_INFO    = 'info';

    /**
     * Genera un mensaje de error validación
     */
    public static function validationError(string $field, string $reason): string
    {
        return "Por favor verifica el campo <strong>$field</strong>: $reason";
    }

    /**
     * Genera un mensaje de éxito
     */
    public static function success(string $message): string
    {
        return "✓ $message";
    }

    /**
     * Genera un mensaje de advertencia
     */
    public static function warning(string $message): string
    {
        return "⚠ $message";
    }

    /**
     * Genera un mensaje de error
     */
    public static function error(string $message): string
    {
        return "✗ $message";
    }

    /**
     * Genera un mensaje informativo
     */
    public static function info(string $message): string
    {
        return "ℹ $message";
    }

    /**
     * Valida peso del producto
     */
    public static function validateWeight(float $weight): array
    {
        $result = ['valid' => true, 'message' => ''];

        if ($weight <= 0) {
            $result['valid'] = false;
            $result['message'] = self::warning(
                "El peso debe ser mayor a 0 kg. Por favor ingresa un peso válido para generar el sticker."
            );
        } elseif ($weight > 9999) {
            $result['valid'] = false;
            $result['message'] = self::warning(
                "El peso parece demasiado alto (máximo 9999 kg). Verifica el valor ingresado."
            );
        }

        return $result;
    }

    /**
     * Genera mensaje de validación de campo requerido
     */
    public static function requiredField(string $fieldName): string
    {
        return self::validationError(
            $fieldName,
            "este campo es obligatorio para continuar"
        );
    }

    /**
     * Genera mensaje de éxito de descarga
     */
    public static function stickerDownloadReady(string $codigo): string
    {
        return self::success(
            "Sticker para <strong>$codigo</strong> listo para imprimir. Se abrirá en una nueva ventana."
        );
    }

    /**
     * Formatea un mensaje con tipo y contenido
     */
    public static function format(string $type, string $message, string $context = ''): array
    {
        $formatted = match($type) {
            self::TYPE_SUCCESS => self::success($message),
            self::TYPE_WARNING => self::warning($message),
            self::TYPE_ERROR   => self::error($message),
            self::TYPE_INFO    => self::info($message),
            default            => $message
        };

        return [
            'type'    => $type,
            'message' => $formatted,
            'context' => $context,
            'icon'    => self::getIcon($type)
        ];
    }

    /**
     * Obtiene el icono según el tipo de mensaje
     */
    private static function getIcon(string $type): string
    {
        return match($type) {
            self::TYPE_SUCCESS => '✓',
            self::TYPE_WARNING => '⚠',
            self::TYPE_ERROR   => '✗',
            self::TYPE_INFO    => 'ℹ',
            default            => ''
        };
    }

    /**
     * Genera mensaje para campo de peso no válido
     */
    public static function invalidWeightMessage(): string
    {
        return self::validationError(
            "Peso",
            "debe ser un número válido mayor a 0"
        );
    }

    /**
     * Genera mensaje cuando el peso es cero
     */
    public static function weightIsZeroMessage(): string
    {
        return self::warning(
            "No es posible generar un sticker con peso de <strong>0 kg</strong>. "
            . "Por favor ingresa el peso definitivo del producto."
        );
    }

    /**
     * Genera mensaje amable de confirmación
     */
    public static function confirmation(string $action): string
    {
        $messages = [
            'print'   => '¿Seguro que deseas imprimir este sticker?',
            'delete'  => '¿Estás seguro de que deseas eliminar esto?',
            'confirm' => '¿Deseas confirmar esta acción?',
        ];

        return $messages[$action] ?? $messages['confirm'];
    }
}
