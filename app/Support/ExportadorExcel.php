<?php

namespace App\Support;

use App\Models\PersonaContacto;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Genera los archivos .xlsx que descarga el administrador
 * (ej. DatosPersonales_Torres.xlsx, DatosEmergencia_Torres.xlsx, como en el Figma).
 */
class ExportadorExcel
{
    /**
     * @param  string  $prefijo  'DatosPersonales' o 'DatosEmergencia'
     * @param  list<string>  $columnas  títulos de la tabla
     * @param  iterable<array<int, string|int|null>>  $filas  una fila por registro, en el orden de las columnas
     */
    public function descargar(string $prefijo, string $titulo, PersonaContacto $profesor, array $columnas, iterable $filas): BinaryFileResponse
    {
        $ruta = tempnam(sys_get_temp_dir(), 'sacpa_');
        $negrita = (new Style)->setFontBold();

        $writer = new Writer;
        $writer->openToFile($ruta);

        $writer->addRow(Row::fromValues([$titulo], $negrita));
        $writer->addRow(Row::fromValues([
            'Profesor:',
            trim("{$profesor->peco_nombre} {$profesor->peco_apellido_paterno} {$profesor->peco_apellido_materno}"),
        ]));
        $writer->addRow(Row::fromValues(['Número de trabajador:', $profesor->peco_numero_trabajador]));
        $writer->addRow(Row::fromValues(['Fecha de descarga:', now()->format('d/m/Y H:i')]));
        $writer->addRow(Row::fromValues([]));
        $writer->addRow(Row::fromValues($columnas, $negrita));

        foreach ($filas as $fila) {
            $writer->addRow(Row::fromValues(array_values($fila)));
        }

        $writer->close();

        // "Peña Nieto" → DatosPersonales_PenaNieto.xlsx
        $apellido = preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($profesor->peco_apellido_paterno)) ?: 'Profesor';
        $nombre = "{$prefijo}_{$apellido}.xlsx";

        return response()
            ->download($ruta, $nombre, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend();
    }
}
