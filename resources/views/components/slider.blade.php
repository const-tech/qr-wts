@push('css')
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <style>
        .swiper {
            width: 100%;
            height: fit-content;
            margin-bottom: 20px;
        }

        .swiper-slide {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
            min-height: 400px;
            border-radius: 10px;
            background: linear-gradient(135deg, #f5f0e8 0%, #e2ffef 100%);
        }

        /* Decorative stars */
        .star {
            position: absolute;
            color: #000;
            font-size: 24px;
        }

        .star-top-left {
            top: 80px;
            left: 60px;
        }

        .star-top-right {
            top: 120px;
            right: 60px;
        }

        .star-bottom-left {
            bottom: 80px;
            left: 80px;
            opacity: 0.3;
        }

        .star-bottom-right {
            bottom: 100px;
            right: 100px;
            opacity: 0.3;
        }

        .content {
            text-align: center;
            z-index: 2;
            padding: 40px;
        }

        .title {
            font-size: 34px;
            font-weight: 800;
            color: #1a1a1a;
            line-height: 1.2;
            margin-bottom: 30px;
        }

        .description {
            font-size: 17px;
            color: #666;
            max-width: 700px;
            margin: 0 auto 30px;
            line-height: 1.6;
        }

        .list-op {
            justify-content: center;

            li {
                color: #666;

                i,
                svg {
                    color: #25d366;
                }
            }
        }

        .buttons {
            display: flex;
            gap: 16px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .btn-slider.btn {
            padding: 16px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-block;
        }

        .btn-slider.btn-primary {
            background-color: #25d366;
            color: white;
        }

        .btn-slider.btn-primary:hover {
            background-color: #1da851;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(18, 140, 91, 0.3);
        }

        .btn-slider.btn-secondary {
            background-color: transparent;
            color: #1a1a1a;
            border: 2px solid #ddd;
        }

        .btn-slider.btn-secondary:hover {
            border-color: #25d366;
            color: #25d366;
        }

        .btn-light {
            background-color: #fff;
        }

        .trial-note {
            font-size: 14px;
            color: #888;
        }

        /* Swiper pagination */
        .swiper-pagination-bullet {
            width: 10px;
            height: 10px;
            background: #ccc;
            opacity: 1;
        }

        .swiper-pagination-bullet-active {
            background: #000;
        }

        /* Swiper navigation */
        .swiper-button-next,
        .swiper-button-prev {
            color: #000;
            width: 50px;
            height: 50px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 20px;
            font-weight: bold;
        }

        /* Different slide variations */
        .slide-2 {
            background: linear-gradient(135deg, #f5f0e8 0%, #e8f5f3 100%);
        }

        .slide-2 .btn-slider.btn-primary {
            background-color: #075e54;
        }

        .slide-2 .btn-slider.btn-secondary:hover {
            border-color: #075e54;
            color: #075e54;
        }

        @media (max-width: 768px) {
            .buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-slider.btn {
                width: 100%;
                max-width: 280px;
            }

            .star {
                display: none;
            }
        }
    </style>
@endpush

<div class="swiper mySliderSwiper">
    <div class="swiper-wrapper">
        <!-- Slides 1: Promo Banner -->
        <x-whatsapp-gateway::promo-banner />

        <!-- Slide 2: Announcement Banner -->
        <x-whatsapp-gateway::announcement-banner theme="indigo" icon="fa-receipt" eyebrow="خدمة جديدة"
            title="الآن لدينا خدمة الربط مع الزكاة — المرحلة الثانية"
            description="وفّر وقتك ورفع فواتيرك إلى منصة الزكاة والدخل مباشرة من برنامجك. جاهزون لمساعدتك في التفعيل والربط خطوة بخطوة."
            phone="0506499275" />
    </div>

    <!-- Pagination -->
    <div class="swiper-pagination"></div>

    <!-- Navigation -->
    <div class="swiper-button-next"></div>
    <div class="swiper-button-prev"></div>
</div>


@push('js')
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        var swiper = new Swiper(".mySliderSwiper", {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: ".swiper-pagination",
                clickable: true,
            },
            navigation: {
                nextEl: ".swiper-button-next",
                prevEl: ".swiper-button-prev",
            },
        });
    </script>
@endpush
