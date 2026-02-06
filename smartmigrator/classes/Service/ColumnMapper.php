<?php
namespace SmartMigrator\Service;

class ColumnMapper
{
    // Heuristic Map: Standard Key => Possible CSV Headers
    private $map = [
        'handle' => ['Handle', 'Reference', 'Code', 'Skull', 'ID'],
        'title' => ['Title', 'Name', 'Nombre', 'Product Name', 'Titulo'],
        'body' => ['Body (HTML)', 'Description', 'Descripción', 'Desc'],
        'vendor' => ['Vendor', 'Manufacturer', 'Marca', 'Fabricante'],
        'type' => ['Type', 'Category', 'Categoria', 'Tipo'],
        'tags' => ['Tags', 'Etiquetas', 'Keywords'],
        'published' => ['Published', 'published_at', 'Publicado', 'Active'],
        'option1_name' => ['Option1 Name', 'Opción 1 Nombre', 'Atributo 1'],
        'option1_value' => ['Option1 Value', 'Opción 1 Valor', 'Valor 1'],
        'option2_name' => ['Option2 Name', 'Opción 2 Nombre', 'Atributo 2'],
        'option2_value' => ['Option2 Value', 'Opción 2 Valor', 'Valor 2'],
        'option3_name' => ['Option3 Name', 'Opción 3 Nombre', 'Atributo 3'],
        'option3_value' => ['Option3 Value', 'Opción 3 Valor', 'Valor 3'],
        'sku' => ['Variant SKU', 'SKU', 'Referencia Variación'],
        'price' => ['Variant Price', 'Price', 'Precio', ' PVP'],
        'quantity' => ['Variant Inventory Qty', 'Quantity', 'Stock', 'Cantidad'],
        'image' => ['Image Src', 'Image', 'Imagen', 'Foto'],
    ];

    public function getMappedHeaders($csvHeaders)
    {
        $mapping = [];
        foreach ($this->map as $standardKey => $candidates) {
            foreach ($candidates as $candidate) {
                // Case insensitive search
                $foundKey = $this->findHeader($csvHeaders, $candidate);
                if ($foundKey !== false) {
                    $mapping[$standardKey] = $foundKey;
                    break;
                }
            }
        }
        return $mapping;
    }

    private function findHeader($headers, $candidate)
    {
        foreach ($headers as $header) {
            if (mb_strtolower(trim($header)) === mb_strtolower($candidate)) {
                return $header;
            }
        }
        return false;
    }

    public function mapRow($row, $mapping)
    {
        $mappedRow = [];
        foreach ($mapping as $standardKey => $originalHeader) {
            $mappedRow[$standardKey] = isset($row[$originalHeader]) ? $row[$originalHeader] : null;
        }
        // Keep original row data too, just in case
        return array_merge($row, $mappedRow);
    }
}
