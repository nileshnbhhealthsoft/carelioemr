<?php

/** The branded header band shared by the entry and print pages. */
function radreq_head(): void
{
    ?>
    <header class="radreq-head">
        <img src="assets/carelio-logo.svg" alt="Carelio EMR">
        <div>
            <h1><?php echo xlt('Radiology Requisition'); ?></h1>
            <p><?php echo xlt('Unified diagnostic imaging order · X-ray · CT · MRI · Ultrasound · Mammography · Fluoroscopy · Nuclear Medicine · Other'); ?></p>
        </div>
    </header>
    <?php
}
