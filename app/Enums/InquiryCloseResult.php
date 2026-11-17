<?php

namespace App\Enums;

enum InquiryCloseResult: string
{
    case Consulted = 'da_tu_van';
    case NotContinuing = 'khach_khong_tiep_tuc';
    case DirectTransaction = 'khach_giao_dich_truc_tiep';
    case Unreachable = 'khong_lien_he_duoc';
}
