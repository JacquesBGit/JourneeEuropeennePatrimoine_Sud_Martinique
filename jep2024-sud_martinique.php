<?php
/*
Plugin Name: JEP 2024 Sud Martinique
Plugin URI: http://espacesud.fr
Description: Affiche les événements des Journées européennes du Patrimoine 2024 au Sud Martinique
Version: 1.0
Author: Jacques Boulogne
Author URI: https://github.com/JacquesBGit
License: GPL2
*/

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit;
}

// Enregistrer les scripts et styles nécessaires
function jep_events_enqueue_scripts() {
    wp_enqueue_script('imagesloaded', 'https://unpkg.com/imagesloaded@5/imagesloaded.pkgd.min.js', array('jquery'), null, true);
    wp_enqueue_style('montserrat-font', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;700&display=swap');
    wp_enqueue_style('jep-events-style', plugin_dir_url(__FILE__) . 'jep-events-style.css');
}
add_action('wp_enqueue_scripts', 'jep_events_enqueue_scripts');

// Créer le shortcode
function jep_events_shortcode() {
    ob_start();
    ?>
    <div id="jep-events">
        <div id="error"></div>
        <div id="events"></div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            const apiKey = '15307cd658ca4075a851e4abbcf725f0';
            const agendaUid = 'jep-2024-martinique';
            const eventsContainer = document.getElementById('events');
            const errorDiv = document.getElementById('error');
            const selectedCities = ['Le François', 'Rivière-Pilote', 'Les Trois-Îlets'];

            function formatDate(dateString) {
                if (!dateString) return null;
                const date = new Date(dateString);
                if (isNaN(date.getTime())) return null;
                return date.toLocaleString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            function getDescription(event) {
                if (typeof event.description === 'string') {
                    return event.description;
                } else if (event.description && event.description.fr) {
                    return event.description.fr;
                } else {
                    return null;
                }
            }

            function getTitle(event) {
                if (typeof event.title === 'string') {
                    return event.title;
                } else if (event.title && event.title.fr) {
                    return event.title.fr;
                } else {
                    return 'Titre non disponible';
                }
            }

            function getImageUrl(event) {
                if (event.image && event.image.base && event.image.filename) {
                    return event.image.base + event.image.filename;
                }
                return null;
            }

            async function getEvents(ville) {
                const url = `https://api.openagenda.com/v2/agendas/${agendaUid}/events?key=${apiKey}&search=${encodeURIComponent(ville)}`;
                const response = await fetch(url);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            }

            async function displayCitiesAndEvents() {
                errorDiv.style.display = 'none';
                try {
                    for (const ville of selectedCities) {
                        const data = await getEvents(ville);
                        if (data.events && data.events.length > 0) {
                            data.events.forEach(event => {
                                console.log('Structure de l\'événement:', JSON.stringify(event, null, 2));
                                const eventDiv = document.createElement('div');
                                eventDiv.className = 'event';
                                let eventHtml = `<div class="city">${ville}</div>`;
                                eventHtml += `<h3>${getTitle(event)}</h3>`;

                                const imageUrl = getImageUrl(event);
                                if (imageUrl) {
                                    eventHtml += `<img src="${imageUrl}" alt="${getTitle(event)}">`;
                                }

                                const description = getDescription(event);
                                if (description) {
                                    eventHtml += `<p><strong>Description:</strong> ${description}</p>`;
                                }

                                if (event.location && event.location.name) {
                                    eventHtml += `<p><strong>Lieu:</strong> ${event.location.name}</p>`;
                                }

                                if (event.location && event.location.address) {
                                    eventHtml += `<p><strong>Adresse:</strong> ${event.location.address}</p>`;
                                }

                                if (event.dateRange && event.dateRange.fr) {
                                    eventHtml += `<p><strong>Dates:</strong> ${event.dateRange.fr}</p>`;
                                }

                                const startDate = formatDate(event.firstTiming.begin);
                                if (startDate) {
                                    eventHtml += `<p><strong>Début:</strong> ${startDate}</p>`;
                                }

                                const endDate = formatDate(event.lastTiming.end);
                                if (endDate) {
                                    eventHtml += `<p><strong>Fin:</strong> ${endDate}</p>`;
                                }

                                if (event.conditions) {
                                    eventHtml += `<p><strong>Conditions:</strong> ${event.conditions}</p>`;
                                }

                                eventHtml += `<p><a href="https://openagenda.com/jep-2024-martinique/events/${event.slug}" target="_blank">Plus d'informations</a></p>`;
                                
                                eventDiv.innerHTML = eventHtml;
                                eventsContainer.appendChild(eventDiv);
                            });
                        }
                    }
                } catch (error) {
                    console.error('Erreur lors de la récupération des données:', error);
                    errorDiv.innerHTML = `<p>Erreur lors de la récupération des données: ${error.message}</p>`;
                    errorDiv.style.display = 'block';
                }
            }

            displayCitiesAndEvents();
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('jep_events', 'jep_events_shortcode');
