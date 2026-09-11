<?php
class block_netflix_dash extends block_base {

    public function init() {
        $this->title = ''; 
    }

    public function get_content() {
        global $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        // --- LÓGICA DINÁMICA DE PHP ---
        $db_courses = $DB->get_records_select('course', 'id != 1', null, 'sortorder ASC');
        $dynamic_courses = [];
        $contador = 0;

        foreach ($db_courses as $c) {
            $context = context_course::instance($c->id);
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'course', 'overviewfiles', false, 'filename', false);
            
            $imageurl = $OUTPUT->image_url('course/default_image', 'theme')->out(false);
            foreach ($files as $file) {
                if ($file->is_valid_image()) {
                    $imageurl = moodle_url::make_pluginfile_url(
                        $file->get_contextid(), $file->get_component(), $file->get_filearea(), null, $file->get_filepath(), $file->get_filename()
                    )->out(false);
                    break;
                }
            }

            $summary = strip_tags($c->summary);
            if (empty($summary)) {
                $summary = "Descubre los detalles de " . $c->fullname . " y desarrolla nuevas habilidades en este curso.";
            }

            $dynamic_courses[] = [
                'id' => $c->id,
                'title' => $c->fullname,
                'description' => $summary,
                'professor' => "Instructor BW", 
                'enrolled' => "Inscripciones abiertas",
                'date' => userdate($c->startdate, '%d %B %Y'),
                'image' => $imageurl,
                'progress' => ($contador === 0) ? 15 : 0
            ];
            $contador++;
        }

        $json_courses = json_encode($dynamic_courses);
        // --- FIN LÓGICA DINÁMICA ---

        ob_start();
        ?>


        <div class="main-content">
            <div class="hero-bg-container">
                <div class="hero-bg-image" id="hero-bg-image"></div>
                <div class="hero-gradient-side"></div>
                <div class="hero-gradient-bottom"></div>
            </div>

            <section class="hero-section" id="hero-section">
                <div class="hero-content">
                    <h1 id="hero-title"></h1>
                    <p id="hero-description"></p>

                    <div class="hero-meta">
                        <span class="meta-item"><strong class="text-on-surface">Profesor:</strong> <span id="hero-professor"></span></span>
                        <span class="meta-divider">|</span>
                        <span class="meta-item"><strong class="text-on-surface">Inscritos:</strong> <span id="hero-enrolled"></span></span>
                        <span class="meta-divider">|</span>
                        <span class="meta-item"><strong class="text-on-surface">Comienzo:</strong> <span id="hero-date"></span></span>
                    </div>

                    <a href="#" class="btn-primary" id="hero-btn">COMENZAR</a>
                </div>
            </section>

            <section class="catalog-section" id="catalog">
                <div class="catalog-header">
                    <h2>Mis Cursos y Recomendaciones</h2>
                </div>
                
                <div class="carousel-container">
                    <button class="carousel-btn left-btn" id="scroll-left" style="display: none;">&#10094;</button>
                    <div class="catalog-rail" id="carousel-track"></div>
                    <button class="carousel-btn right-btn" id="scroll-right">&#10095;</button>
                </div>
            </section>
        </div>

        <script>
        (function() {
            // INYECCIÓN DINÁMICA DE PHP A JAVASCRIPT
            const courses = <?php echo $json_courses; ?>;

            function initNetflixDash() {
                const track = document.getElementById("carousel-track");
                const heroBgImage = document.getElementById("hero-bg-image");
                const heroTitle = document.getElementById("hero-title");
                const heroDescription = document.getElementById("hero-description");
                const heroProfessor = document.getElementById("hero-professor");
                const heroEnrolled = document.getElementById("hero-enrolled");
                const heroDate = document.getElementById("hero-date");
                const heroBtn = document.getElementById("hero-btn");

                if (!track || !heroTitle || courses.length === 0) return;

                track.innerHTML = "";

                function updateHero(course) {
                    heroTitle.style.opacity = 0;
                    heroDescription.style.opacity = 0;

                    setTimeout(() => {
                        heroBgImage.style.backgroundImage = 'url("' + course.image + '")';
                        heroTitle.innerHTML = course.title;
                        heroDescription.textContent = course.description;
                        heroProfessor.textContent = course.professor;
                        heroEnrolled.textContent = course.enrolled;
                        heroDate.textContent = course.date;
                        heroBtn.href = '/course/view.php?id=' + course.id;

                        heroTitle.style.transition = 'opacity 0.3s ease';
                        heroDescription.style.transition = 'opacity 0.3s ease';
                        heroTitle.style.opacity = 1;
                        heroDescription.style.opacity = 1;
                    }, 150);
                }

                updateHero(courses[0]);

                courses.forEach((course, index) => {
                    const card = document.createElement("a");
                    card.href = '/course/view.php?id=' + course.id;
                    card.className = "course-card " + (index === 0 ? 'active' : '');
                    card.dataset.id = course.id;
                    card.style.textDecoration = 'none';

                    card.innerHTML = 
                        '<div class="card-image-container">' +
                            '<div class="card-bg" style="background-image: url(\'' + course.image + '\')"></div>' +
                            '<div class="card-overlay"></div>' +
                            '<div class="card-info-overlay">' +
                                '<h3 class="card-title">' + course.title + '</h3>' +
                            '</div>' +
                            '<div class="card-progress-track" style="' + (course.progress > 0 ? 'display: block;' : '') + '">' +
                                '<div class="card-progress-fill" style="width: ' + course.progress + '%;"></div>' +
                            '</div>' +
                        '</div>';

                    card.addEventListener("click", (e) => {
                        e.preventDefault(); // Evita navegar si solo quieres actualizar el hero
                        document.querySelectorAll(".course-card").forEach(c => c.classList.remove("active"));
                        card.classList.add("active");
                        updateHero(course);
                    });

                    card.addEventListener("dblclick", () => {
                        window.location.href = card.href; // Navegar al curso en doble clic
                    });

                    track.appendChild(card);
                });

                const scrollLeftBtn = document.getElementById("scroll-left");
                const scrollRightBtn = document.getElementById("scroll-right");

                if (scrollRightBtn) {
                    scrollRightBtn.addEventListener("click", () => {
                        track.scrollBy({ left: track.clientWidth * 0.7, behavior: 'smooth' });
                    });
                }

                if (scrollLeftBtn) {
                    scrollLeftBtn.addEventListener("click", () => {
                        track.scrollBy({ left: -track.clientWidth * 0.7, behavior: 'smooth' });
                    });
                }

                track.addEventListener("scroll", () => {
                    if (scrollLeftBtn) scrollLeftBtn.style.display = track.scrollLeft > 10 ? "flex" : "none";
                    if (scrollRightBtn) {
                        const isAtEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 10;
                        scrollRightBtn.style.display = isAtEnd ? "none" : "flex";
                    }
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initNetflixDash);
            } else {
                initNetflixDash();
            }
        })();
        </script>

        <?php
        $html = ob_get_clean();
        $this->content->text = $html;
        return $this->content;
    }
}
