<?php
// Hrvatski plural za minute/sate/tjedne/mjesece/godine.
// Pravila: 1 -> one, 2-4 -> few, 5+ i 11-14 -> many.
function hr_plural($n, $one, $few, $many) {
    $n = abs((int)$n);
    $mod10  = $n % 10;
    $mod100 = $n % 100;
    if ($mod100 >= 11 && $mod100 <= 14) return $many;
    if ($mod10 === 1) return $one;
    if ($mod10 >= 2 && $mod10 <= 4) return $few;
    return $many;
}

/**
 * Relativno vrijeme na HR (npr. "prije 3 sata"), s prebacivanjem na datum nakon $max_days.
 * @param int $comment_id
 * @param int $max_days  Koliko dana prikazivati "prije X" prije prelaska na datum (default 30)
 * @param string $date_format Format datuma kad je starije od $max_days (default 'j.n.Y.')
 */
function hr_time_ago_for_comment($comment_id, $max_days = 30, $date_format = 'j.n.Y.') {
    $c = get_comment($comment_id);
    if (!$c) return '';
    $ts  = get_comment_time('U', true, $c);
    $now = current_time('timestamp');
    $diff = max(0, $now - $ts);

    // Prebaci na običan datum nakon $max_days
    if ($diff > DAY_IN_SECONDS * $max_days) {
        return date_i18n($date_format, $ts);
    }

    if ($diff < 5) {
        return 'upravo sada';
    }
    if ($diff < MINUTE_IN_SECONDS) {
        return 'prije nekoliko sekundi';
    }

    $mins = floor($diff / MINUTE_IN_SECONDS);
    if ($mins < 60) {
        $label = hr_plural($mins, 'minutu', 'minute', 'minuta');
        return sprintf('prije %s %s', number_format_i18n($mins), $label);
    }

    $hours = floor($diff / HOUR_IN_SECONDS);
    if ($hours < 24) {
        // sat / sata / sati
        $label = hr_plural($hours, 'sat', 'sata', 'sati');
        return sprintf('prije %s %s', number_format_i18n($hours), $label);
    }

    $days = floor($diff / DAY_IN_SECONDS);
    if ($days < 7) {
        // dan / dana (u praksi: 1 dan, 2+ dana)
        $label = ($days == 1) ? 'dan' : 'dana';
        return sprintf('prije %s %s', number_format_i18n($days), $label);
    }

    $weeks = floor($days / 7);
    if ($weeks < 5) {
        // tjedan / tjedna / tjedana
        $label = hr_plural($weeks, 'tjedan', 'tjedna', 'tjedana');
        return sprintf('prije %s %s', number_format_i18n($weeks), $label);
    }

    // (Neće se dogoditi jer prije toga prelazimo na datum, ali za svaki slučaj:)
    return date_i18n($date_format, $ts);
}
