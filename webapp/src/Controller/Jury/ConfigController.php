<?php declare(strict_types=1);

namespace App\Controller\Jury;

use App\Entity\Configuration;
use App\Service\CheckConfigService;
use App\Service\DOMJudgeService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/jury/config")
 * @IsGranted("ROLE_ADMIN")
 */
class ConfigController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var DOMJudgeService
     */
    protected $dj;

    /**
     * @var CheckConfigService
     */
    protected $CheckConfigService;

    /**
     * TeamCategoryController constructor.
     * @param EntityManagerInterface $em
     * @param DOMJudgeService        $dj
     * @param CheckConfigService     $checkConfigService
     */
    public function __construct(
        EntityManagerInterface $em,
        DOMJudgeService $dj,
        CheckConfigService $checkConfigService
    ) {
        $this->em                 = $em;
        $this->dj                 = $dj;
        $this->checkConfigService = $checkConfigService;
    }

    /**
     * @Route("", name="jury_config")
     */
    public function indexAction(Request $request)
    {
        /** @var Configuration[] */
        $options = $this->em->getRepository(Configuration::class)->findAll();
        if ($request->getMethod() == 'POST' && $request->request->has('save')) {
            $this->addFlash('scoreboard_refresh', 'After changing specific settings, you might need to refresh the scoreboard.');
            foreach ($options as $option) {
                if ($option->getType() == 'bool') {
                    $val = $request->request->has('config_' . $option->getName());
                    $option->setValue($val);
                    continue;
                }
                if (!$request->request->has('config_' . $option->getName())) {
                    continue;
                }
                if ($option->getType() == 'int' || $option->getType() == 'string') {
                    $option->setValue($request->request->get('config_' . $option->getName()));
                } else if ($option->getType() == 'array_val') {
                    $vals = $request->request->get('config_' . $option->getName());
                    $result = array();
                    foreach ($vals as $data) {
                        if (!empty($data)) {
                            $result[] = $data;
                        }
                    }
                    $option->setValue($result);
                } else if ($option->getType() == 'array_keyval') {
                    $vals = $request->request->get('config_' . $option->getName());
                    $result = array();
                    foreach ($vals as $data) {
                        if (!empty($data['key'])) {
                            $result[$data['key']] = $data['val'];
                        }
                    }
                    $option->setValue($result);
                }
            }

            $this->em->flush();
            return $this->redirectToRoute('jury_config');
        }
        /** @var Configuration[] */
        $options = $this->em->getRepository(Configuration::class)->findAll();
        $categories = array();
        foreach ($options as $option) {
            if (!in_array($option->getCategory(), $categories)) {
                $categories[] = $option->getCategory();
            }
        }
        $all_data = array();
        foreach ($categories as $category) {
            $data = array();
            foreach ($options as $option) {
                if ($option->getCategory() !== $category) {
                    continue;
                }
                $data[] = array(
                    'name' => $option->getName(),
                    'type' => $option->getType(),
                    'value' => $option->getValue(),
                    'description' => $option->getDescription()
                );
            }
            $all_data[] = array(
                'name' => $category,
                'data' => $data
            );
        }
        return $this->render('jury/config.html.twig', [
            'options' => $all_data,
        ]);
    }

    /**
     * @Route("/check", name="jury_config_check")
     */
    public function checkAction(Request $request)
    {
        $results = $this->checkConfigService->runAll();
        return $this->render('jury/config_check.html.twig', [
            'results' => $results
        ]);
    }

    /**
     * @Route("/check/phpinfo", name="jury_config_phpinfo")
     */
    public function phpinfoAction(Request $request)
    {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_contents();
        ob_end_clean();

        return new Response($phpinfo);
    }
}
