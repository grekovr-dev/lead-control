import '../css/app.css';
import './bootstrap'
import Alpine from 'alpinejs'
import landingCapture from './landingCapture'

window.Alpine = Alpine

document.addEventListener('alpine:init', () => {
    Alpine.data('landingCapture', landingCapture)
})

Alpine.start()
