<?php

/* Normaliza la PK de la hoja para que el resto del archivo no dependa
   del nombre exacto que traiga el SELECT. */
$hojaId = (int)($hoja['id_hojas'] ?? 0);

[$claseBadge, $tituloBadge] = badgeEstatus($hoja['estatus']);

?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">

    <span class="<?= $claseBadge ?> badge fs-6">
        <?= $tituloBadge ?>
    </span>

    <button
        data-csp-print
        class="btn btn-brand btn-sm"
    >
        <i class="bi bi-file-earmark-pdf me-1"></i>
        Descargar / Imprimir PDF
    </button>

</div>


<div class="print-sheet">


    <!-- =====================================================
         ENCABEZADO
    ====================================================== -->

    <div class="ps-head">

        <img
            src="<?= $rutaBase ?>assets/img/logo.png"
            alt="Logo del sistema"
        >


        <div>

            <h1>
                HOJA DE SERVICIO
            </h1>

            <div class="ps-sub">
                la institución —
                el área de soporte técnico
            </div>

        </div>


        <div class="ms-auto text-end">

            <div class="ps-label">
                N° de hoja
            </div>

            <div
                class="ps-value"
                style="font-family:var(--font-display);font-size:1.1rem;"
            >
                N° <?= numeroHoja($hojaId) ?>
            </div>

        </div>

    </div>



    <!-- =====================================================
         1. DATOS GENERALES
    ====================================================== -->

    <p
        class="fw-bold small text-uppercase"
        style="letter-spacing:.04em;color:var(--ink-soft);"
    >
        1. Datos generales
    </p>


    <div class="ps-row">


        <!-- Técnico -->

        <div>

            <div class="ps-label">
                Técnico / Especialista
            </div>

            <div class="ps-value">
                <?= h($hoja['tecnico'] ?? '') ?>
            </div>

        </div>


        <!-- Departamento -->

        <div>

            <div class="ps-label">
                Departamento u oficina solicitante
            </div>

            <div class="ps-value">
                <?= h($hoja['departamento'] ?? '') ?>
            </div>

        </div>


        <!-- Fecha -->

        <div>

            <div class="ps-label">
                Fecha
            </div>

            <div class="ps-value">
                <?= date(
                    'd/m/Y',
                    strtotime($hoja['fecha'])
                ) ?>
            </div>

        </div>

        <?php if (!empty($hoja['ticket_id'])): ?>
        <div>
            <div class="ps-label">Ticket relacionado</div>
            <div class="ps-value">#<?= str_pad((string)$hoja['ticket_id'], 6, '0', STR_PAD_LEFT) ?></div>
        </div>
        <?php endif; ?>

    </div>



    <!-- =====================================================
         2. TIPO DE SERVICIO
    ====================================================== -->

    <p
        class="fw-bold small text-uppercase"
        style="letter-spacing:.04em;color:var(--ink-soft);"
    >
        2. Tipo de servicio
    </p>


    <ul class="ps-check-grid">

        <?php foreach ($tipos as $t): ?>

            <li class="<?= $t['marcado'] ? 'marcado' : '' ?>">

                <?= h($t['codigo']) ?>.
                <?= h($t['nombre']) ?>


                <?php if (
                    $t['codigo'] == 10
                    && $t['marcado']
                    && !empty($hoja['otro_especifique'])
                ): ?>

                    —
                    <?= h($hoja['otro_especifique']) ?>

                <?php endif; ?>

            </li>

        <?php endforeach; ?>

    </ul>



    <!-- =====================================================
         3. TIEMPO DE EJECUCIÓN
    ====================================================== -->

    <p
        class="fw-bold small text-uppercase"
        style="letter-spacing:.04em;color:var(--ink-soft);"
    >
        3. Tiempo de ejecución
    </p>


    <div class="ps-row">


        <!-- Hora inicio -->

        <div>

            <div class="ps-label">
                Hora de inicio
            </div>

            <div class="ps-value">

                <?= !empty($hoja['hora_inicio'])
                    ? substr($hoja['hora_inicio'], 0, 5)
                    : '—'
                ?>

            </div>

        </div>


        <!-- Hora fin -->

        <div>

            <div class="ps-label">
                Hora de finalización
            </div>

            <div class="ps-value">

                <?= !empty($hoja['hora_fin'])
                    ? substr($hoja['hora_fin'], 0, 5)
                    : '—'
                ?>

            </div>

        </div>


        <!-- Total -->

        <div>

            <div class="ps-label">
                Total
            </div>

            <div class="ps-value">

                <?= minutosATexto(
                    $hoja['tiempo_total_minutos'] ?? null
                ) ?>

            </div>

        </div>

    </div>



    <!-- =====================================================
         4. DETALLE DEL SERVICIO
    ====================================================== -->

    <p
        class="fw-bold small text-uppercase"
        style="letter-spacing:.04em;color:var(--ink-soft);"
    >
        4. Detalle del servicio y materiales utilizados
    </p>


    <p
        class="mb-3"
        style="font-size:.9rem;"
    >

        <?= nl2br(
            h($hoja['descripcion'] ?? '')
        ) ?>

    </p>

    <?php if (!empty($evidencia)): ?>

        <p
            class="fw-bold small text-uppercase"
            style="letter-spacing:.04em;color:var(--ink-soft);"
        >
            Evidencia del servicio
        </p>

        <?php $evidenciaExt = strtolower(pathinfo($evidencia, PATHINFO_EXTENSION)); ?>

        <?php if (in_array($evidenciaExt, ['jpg', 'jpeg', 'png', 'webp'], true)): ?>

            <div class="ps-evidencia mb-3">
                <img
                    src="<?= $rutaBase ?>includes/archivo_privado.php?tipo=evidencia&amp;id=<?= $hojaId ?>"
                    alt="Evidencia del servicio"
                >
            </div>

        <?php else: ?>

            <p class="mb-3">
                <a href="<?= $rutaBase ?>includes/archivo_privado.php?tipo=evidencia&amp;id=<?= $hojaId ?>" target="_blank" rel="noopener">
                    <i class="bi bi-paperclip me-1"></i>Ver evidencia adjunta
                </a>
            </p>

        <?php endif; ?>

    <?php endif; ?>



    <!-- =====================================================
         5. CONFORMIDAD
    ====================================================== -->

    <p
        class="fw-bold small text-uppercase"
        style="letter-spacing:.04em;color:var(--ink-soft);"
    >
        5. Conformidad
    </p>


    <div class="ps-row">


        <!-- Usuario atendido -->

        <div>

            <div class="ps-label">
                Usuario atendido
            </div>


            <div class="ps-value mb-2">

                <?= h(
                    $hoja['usuario_atendido_nombre'] ?? ''
                ) ?>

            </div>


            <div class="ps-firma-box">


                <?php if (!empty($hoja['firma_imagen'])): ?>

                    <img
                        src="<?= $rutaBase ?>includes/archivo_privado.php?tipo=firma&amp;id=<?= $hojaId ?>"
                        alt="Firma del usuario atendido"
                    >


                <?php elseif (!empty($hoja['documento_adjunto'])): ?>

                    <a
                        href="<?= $rutaBase ?>includes/archivo_privado.php?tipo=adjunto&amp;id=<?= $hojaId ?>"
                        target="_blank"
                        rel="noopener"
                        class="small"
                    >

                        <i class="bi bi-paperclip me-1"></i>

                        Ver documento firmado adjunto

                    </a>


                <?php else: ?>

                    <span class="text-muted small">
                        Sin firma registrada
                    </span>

                <?php endif; ?>

            </div>

        </div>



        <!-- Técnico -->

        <div class="ps-technician-signature">

            <div class="ps-label">
                Especialista / técnico
            </div>


            <div class="ps-value mb-2">

                <?= h($hoja['tecnico'] ?? '') ?>

            </div>

        </div>

    </div>


</div>