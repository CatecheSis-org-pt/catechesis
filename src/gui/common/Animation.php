<?php

namespace catechesis\gui;

/**
 * Abstracts Animate.CSS animations to apply in the modal dialog widget.
 */
abstract class Animation
{
    const NONE = null;
    const FADE_IN_DOWN = "animate__fadeInDown";
    const FADE_OUT_UP = "animate__fadeOutUp";
    const BACK_IN_DOWN = "animate__backInDown animate__faster";
    const BACK_OUT_UP = "animate__backOutUp animate__faster";
    const FLIP_IN_X = "animate__flipInX";
    const RUBBER = "animate__rubberBand";
    const TADA = "animate__tada";
    const SOFT_SHAKE = "animate__headShake";
    const SHAKE_X = "animate__shakeX";
    const BOUNCE = "animate__bounce";
}