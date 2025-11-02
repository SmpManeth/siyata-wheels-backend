<?php

return [
    // business rules (change if needed)
    'vat_rate' => 0.18,             // 18%
    'amount_without_vat_ratio' => 0.82, // 82% of total (complements VAT 18%)
    'number_prefix' => 'INV-',
    'number_pad' => 3,              // INV-001, INV-002...
];
