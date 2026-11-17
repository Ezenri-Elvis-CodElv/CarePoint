<?php
/**
 * Admin-only UI helpers.
 */

/** Approve / reject quick-action buttons for a pending appointment. */
function admin_quick_actions(array $a, string $return): string
{
    $html = '<div class="row-actions">';
    $html .= '<a href="' . url('admin/appointment.php?id=' . (int)$a['id']) . '" class="icon-btn" title="View details"><i class="fa-solid fa-eye"></i></a>';
    if ($a['status'] === 'Pending') {
        foreach ([['Confirmed', 'icon-btn-success', 'fa-check', 'Approve'], ['Rejected', 'icon-btn-danger', 'fa-xmark', 'Reject']] as [$status, $cls, $icon, $title]) {
            $confirm = $status === 'Rejected' ? ' data-confirm="Reject appointment ' . e($a['reference']) . '? The patient will see it as rejected."' : '';
            $html .= '<form method="post" action="' . url('admin/appointment-action.php') . '"' . $confirm . '>'
                . csrf_field()
                . '<input type="hidden" name="id" value="' . (int)$a['id'] . '">'
                . '<input type="hidden" name="status" value="' . $status . '">'
                . '<input type="hidden" name="return" value="' . e($return) . '">'
                . '<button type="submit" class="icon-btn ' . $cls . '" title="' . $title . '"><i class="fa-solid ' . $icon . '"></i></button></form>';
        }
    }
    return $html . '</div>';
}

/** Validate & normalise a return URL so we only ever redirect inside admin/. */
function admin_return_url(string $return, string $fallback = 'admin/appointments.php'): string
{
    $return = ltrim($return, '/');
    return preg_match('#^admin/[a-z\-]+\.php(\?[A-Za-z0-9_=&%\-.+:@]*)?$#', $return) ? $return : $fallback;
}
